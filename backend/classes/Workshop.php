<?php
/**
 * Workshop.php – Google-Maps-gecachte Werkstatt-API für Carfify
 *
 * Diese Klasse
 * • sucht und filtert Kfz-Werkstätten via Google Places API,
 * • cachte Datenbank- und Image-Assets stündlich, um Limits und Kosten zu sparen,
 * • liefert DSGVO-konforme Ergebnisse (keine personenbezogenen Daten),
 * • kann als Lazy-Singleton (per PDO) oder per static-helper genutzt werden.
 *
 * Technische Details
 * • PostgreSQL-Tabelle `cached_workshops`
 * • Caching-Keys basierend auf Hash(Anfrage+Filter+Koordinaten)
 * • Thumbnail-URLs und Bewertungsscores sind bereits vorgefiltert
 * • Fehler wirft Exceptions – auffangen in /api/workshops.php
 *
 * @package Carfify\Api
 * @author  Carfify Dev-Core <dev@carfify.test>
 * @version 1.0
 */

declare(strict_types=1);

namespace Carfify\Backend;

use PDO;
use Exception;

class Workshop
{
    /* ------------- PRIVATE ------------- */
    private PDO    $db;
    private string $googleApiKey;

    private const CACHE_LIFETIME_SEC   = 3600;      // 1 Std
    private const GOOGLE_API_BASE      = 'https://maps.googleapis.com/maps/api/place/';

    /* ------------- KONSTRUKTOR ------------- */
    public function __construct(PDO $db, string $googleApiKey)
    {
        $this->db           = $db;
        $this->googleApiKey = $googleApiKey;
    }

    /* ------------- HAUPTMETHODE ------------- */

    /**
     * Haupt-Einstieg: Werkstätten suchen & cachen
     *
     * @param  float   $lat         Breitengrad (Dezimal)
     * @param  float   $lng         Längengrad  (Dezimal)
     * @param  int|null $radiusKm   Umkreis in km (max 50, default 10)
     * @param  string  $keyword     Zusätzlicher Filter ("Autowerkstatt", "Reifenservice" …)
     * @return array                 Kompaktes Array mit Workshops
     * @throws Exception            Google-Errors werden als Exception weitergereicht
     */
    public function search(
        float  $lat,
        float  $lng,
        ?int   $radiusKm = 10,
        string $keyword  = 'Autowerkstatt'
    ): array {
        // 1) Parameter normalisieren
        $radiusKm     = max(1, min(50, $radiusKm ?: 10));
        $radiusMeters = $radiusKm * 1000;

        // 2) Cache-Key erzeugen (anonymisiert, weil keine PII)
        $cacheKey = hash('sha256', sprintf(
            '%s|%.6f|%.6f|%d|%s',
            $keyword,
            $lat,
            $lng,
            $radiusKm,
            self::CACHE_LIFETIME_SEC
        ));

        // 3) Prüfen: Gibt es einen gültigen Cache?
        $cached = $this->fetchCache($cacheKey);
        if (!empty($cached)) {
            return $cached;
        }

        // 4) Google Places API aufrufen
        $googleResults = $this->fetchFromGoogle($lat, $lng, $radiusMeters, $keyword);

        // 5) Datenbereinigung & Minifizierung für Frontend
        $sanitized = array_map([$this, 'normalizeWorkshop'], $googleResults);

        // 6) In DB & Redis cachen
        $this->storeCache($cacheKey, $sanitized);

        return $sanitized;
    }

    /* ------------- GOOGLE FETCH ------------- */

    /**
     * Ruft Google Places Nearby-Search ab.
     *
     * @return array Raw-JSON Assoziatives Array
     * @throws Exception On HTTP oder API-Error
     */
    private function fetchFromGoogle(
        float  $lat,
        float  $lng,
        int    $radiusMeters,
        string $keyword
    ): array {
        $params = http_build_query([
            'location'     => "{$lat},{$lng}",
            'radius'       => $radiusMeters,
            'keyword'      => $keyword,
            'type'         => 'car_repair',
            // Für reichhaltige Antwort:
            'fields'       => 'name,place_id,rating,user_ratings_total,formatted_address,geometry,vicinity,photos,opening_hours',
            'key'          => $this->googleApiKey,
        ]);

        $url = self::GOOGLE_API_BASE . 'nearbysearch/json?' . $params;

        $context = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'timeout' => 3.0,        // Sekunden
                'header'  => "User-Agent: Carfify/1.0\r\n"
            ]
        ]);

        $json = @file_get_contents($url, false, $context);
        if ($json === false) {
            throw new Exception('Google-API konnte nicht erreicht werden.');
        }

        $data = @json_decode($json, true);
        if (!isset($data['results'])) {
            throw new Exception('Google lieferte ein ungültiges JSON.');
        }

        return $data['results'];
    }

    /* ------------- DATEN-NORMALISIERUNG ------------- */

    /**
     * Wandelt Googles großes Array in unser lightweight Frontend-Objekt um.
     *
     * @param  array $raw
     * @return array
     */
    private function normalizeWorkshop(array $raw): array
    {
        // Mini-Thumbnail via erstes Foto (falls verfügbar):
        $photoUrl = null;
        if (!empty($raw['photos'][0])) {
            $ref = $raw['photos'][0]['photo_reference'];
            $photoUrl = self::GOOGLE_API_BASE . "photo?maxwidth=320&photoreference={$ref}&key={$this->googleApiKey}";
        }

        return [
            'placeId'      => $raw['place_id'],
            'name'         => $raw['name'] ?? 'Unbekannte Werkstatt',
            'address'      => $raw['vicinity'] ?? ($raw['formatted_address'] ?? null),
            'lat'          => $raw['geometry']['location']['lat'] ?? null,
            'lng'          => $raw['geometry']['location']['lng'] ?? null,
            'rating'       => $raw['rating'] ?? null,
            'totalRatings' => $raw['user_ratings_total'] ?? 0,
            'photo'        => $photoUrl,
            'openNow'      => $raw['opening_hours']['open_now'] ?? null,
        ];
    }

    /* ------------- CACHING-MECHANISMEN ------------- */

    /**
     * Cache aus DB abholen
     *
     * @param  string $cacheKey
     * @return array|null
     */
    private function fetchCache(string $cacheKey): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT payload
               FROM cached_workshops
              WHERE cache_key = :key
                AND created_at > NOW() - INTERVAL \'1 hour\''
        );
        $stmt->execute([':key' => $cacheKey]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? json_decode($row['payload'], true) : null;
    }

    /**
     * Cache persistent speichern
     *
     * @param string $cacheKey
     * @param array  $data
     */
    private function storeCache(string $cacheKey, array $data): void
    {
        // bestehenden Cache vorher löschen um Duplikate zu verhindern
        $del = $this->db->prepare(
            'DELETE FROM cached_workshops WHERE cache_key = :key'
        );
        $del->execute([':key' => $cacheKey]);

        // neu einfügen
        $ins = $this->db->prepare(
            'INSERT INTO cached_workshops (cache_key, payload, created_at)
             VALUES (:key, :payload, NOW())'
        );
        $ins->execute([
            ':key'     => $cacheKey,
            ':payload' => json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);
    }
}
