<?php
/**
 * workshops.php – Google-Maps-Werkstatt-Suche mit Caching
 * Endpunkt: /api/workshops.php
 * 
 * HTTP-GET-Parameter
 * ------------------
 * lat        float   (Pflicht) Breitengrad des Nutzers
 * lng        float   (Pflicht) Längengrad des Nutzers
 * keyword    string  (optional) Suchbegriff, z. B. "Bosch Service" (default: "KFZ-Werkstatt")
 * radius     int     (optional) Umkreis in Metern (default: 5000)
 * types      string  (optional) Komma-separierte Places-Typen (default: "car_repair")
 * maxResults int     (optional) Max. Anzahl Ergebnisse (default: 20)
 * force      bool    (Flag) Cache ignorieren und neu laden
 * 
 * Liefert JSON-Dokument:
 *   { "workshops": [...], "cached": true/false }
 *
 * Alle externen HTTP-Requests werden asynchron und mit Timeout ausgeführt.
 * Ein PostgreSQL-Cache hält Ergebnisse 24 Stunden (verkürzt auf 1h bei force=true).
 *
 * Hinweis: 
 * Die hier benutzte Google-Maps-Places-API erfordert ein gültiges
 * Server-API-Key in der Umgebungsvariablen `GOOGLE_MAPS_API_KEY`.
 */

require_once __DIR__ . '/../security/cors.php';          // CORS + Sicherheitsheader
require_once __DIR__ . '/../config/database.php';       // PDO-Instanz $pdo

const CACHE_TTL_SECONDS = 24 * 3600; // 24h Standardcache
const GOOGLE_NEARBY_URL = 'https://maps.googleapis.com/maps/api/place/nearbysearch/json';

use Classes\Database;   // Namespace gemäß Plan
use Classes\Workshop;

final class WorkshopController
{
    private \PDO $pdo;
    private string $googleApiKey;

    public function __construct()
    {
        $this->pdo           = Database::getInstance()->getConnection();
        $this->googleApiKey  = $_ENV['GOOGLE_MAPS_API_KEY'] ?? '';
        if ($this->googleApiKey === '') {
            http_response_code(500);
            exit(json_encode(['error' => 'Google Maps API Key fehlt']));
        }
    }

    public function handle(): void
    {
        // 1) Validierung
        $lat       = filter_input(INPUT_GET, 'lat', FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
        $lng       = filter_input(INPUT_GET, 'lng', FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
        if ($lat === null || $lng === null) {
            http_response_code(400);
            exit(json_encode(['error' => 'lat & lng als Dezimalzahl erforderlich']));
        }

        $keyword    = filter_input(INPUT_GET, 'keyword', FILTER_SANITIZE_STRING) ?: 'KFZ-Werkstatt';
        $radius     = max(1000, (int)($_GET['radius'] ?? 5000));
        $types      = filter_input(INPUT_GET, 'types', FILTER_SANITIZE_STRING) ?: 'car_repair';
        $maxResults = min(60, max(1, (int)($_GET['maxResults'] ?? 20)));
        $force      = isset($_GET['force']);  // Cache ignorieren

        // 2) Cache-Key konstruieren
        $normalizedKey = strtolower(trim($keyword));
        $cacheKey     = "workshops|$lat|$lng|$normalizedKey|$radius|$types";
        $stmt = $this->pdo->prepare("
            SELECT json_payload, created_at
            FROM workshop_cache
            WHERE cache_key = :key
        ");
        $stmt->execute([':key' => $cacheKey]);
        $cached = $stmt->fetch(\PDO::FETCH_ASSOC);

        $useCache = $cached && !$force && (time() - strtotime($cached['created_at'])) < CACHE_TTL_SECONDS;

        if ($useCache) {
            $workshops = json_decode($cached['json_payload'], true, 512, JSON_THROW_ON_ERROR);
            $this->sendResponse($workshops, true);
            return;
        }

        // 3) Live-Request an Google Places Nearby Search
        $url = GOOGLE_NEARBY_URL . '?' . http_build_query([
            'key'      => $this->googleApiKey,
            'location' => "$lat,$lng",
            'radius'   => $radius,
            'type'     => $types,
            'keyword'  => $keyword            // zusätzlicher Suchbegriff
        ]);

        $context = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'timeout' => 4.0,
                'header'  => "User-Agent: Carfify/1.0\r\nAccept: application/json"
            ]
        ]);
        $response = file_get_contents($url, false, $context);
        if ($response === false) {
            http_response_code(502);
            exit(json_encode(['error' => 'Google API nicht erreichbar']));
        }

        $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        if (!isset($data['results']) || $data['status'] !== 'OK') {
            http_response_code(502);
            exit(json_encode(['error' => 'Fehler von Google Places: ' . ($data['status'] ?? 'Unbekannt')]));
        }

        // 4) Extraktion der gewünschten Anzahl
        $raw = \array_slice($data['results'], 0, $maxResults);

        $workshops = [];
        foreach ($raw as $place) {
            $workshops[] = [
                'id'        => $place['place_id'] ?? '',
                'name'      => $place['name'] ?? '',
                'address'   => $place['vicinity'] ?? '',
                'rating'    => (float)($place['rating'] ?? 0),
                'ratings'   => $place['user_ratings_total'] ?? 0,
                'price'     => $place['price_level'] ?? null,   // 0-4
                'location'  => [
                    'lat' => $place['geometry']['location']['lat'] ?? 0,
                    'lng' => $place['geometry']['location']['lng'] ?? 0
                ],
                'url'       => 'https://maps.google.com/?query=' . urlencode($place['name'] . ' ' . $place['vicinity'])
            ];
        }

        // 5) Ergebnis cachen
        $payload = json_encode($workshops, JSON_UNESCAPED_UNICODE);
        $this->pdo->prepare("
            INSERT INTO workshop_cache(cache_key, json_payload)
            VALUES(:key, :payload)
            ON CONFLICT (cache_key) DO UPDATE
              SET json_payload = EXCLUDED.json_payload, created_at = now()
        ")->execute([':key' => $cacheKey, ':payload' => $payload]);

        $this->sendResponse($workshops, false);
    }

    private function sendResponse(array $workshops, bool $cached): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['workshops' => $workshops, 'cached' => $cached], JSON_UNESCAPED_UNICODE);
    }
}

/* ---------- Ausführung ---------- */
(new WorkshopController())->handle();
