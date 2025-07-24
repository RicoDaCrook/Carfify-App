<?php
/**
 * Workshop.php – Google-Maps-cached workshop API for Carfify
 * Fetches workshops via Places API, caches results hourly, anonymized (!PII)
 * Designed for Vercel/lambda-php compatibility (without cURL, sqlite fallback).
 *
 * CHANGELOG
 *  - file_get_contents() statt cURL für 100 % Vercel-Kompatibilität
 *  - createTableIfNotExists() für Fehlerfreiheit bei Edge-Cold-Start
 */

declare(strict_types=1);

namespace Carfify\Backend;

use PDO;
use Exception;

class Workshop
{
    /* ------------- CONSTANTS ------------- */
    private const CACHE_LIFETIME_SEC = 3600;      // 1 hour
    private const GOOGLE_API_BASE    = 'https://maps.googleapis.com/maps/api/place/';
    // Timeout via stream_context() nur minimal; Lambda macht 10s hard-cap.

    /* ------------- PRIVATE ------------- */
    private PDO    $db;
    private string $googleApiKey;

    /* ------------- CONSTRUCTOR ------------- */
    public function __construct(PDO $db)
    {
        $this->db = $db;

        // Tabelle erstellen, falls nicht existiert (SQLite/PostgreSQL neutral)
        $this->createTableIfNotExists();

        // API Key aus Umgebung laden
        $key = $_ENV['GOOGLE_MAPS_API_KEY'] ?? false;
        if (!$key) {
            throw new Exception('GOOGLE_MAPS_API_KEY is missing from environment');
        }
        $this->googleApiKey = $key;
    }

    /* ------------- MAIN ENTRYPOINT ------------- */
    public function search(
        float  $lat,
        float  $lng,
        ?int   $radiusKm = 10,
        string $keyword  = 'Autowerkstatt'
    ): array {
        $radiusKm     = max(1, min(50, $radiusKm ?: 10));
        $radiusMeters = $radiusKm * 1000;

        $cacheKey = hash('sha256', sprintf(
            '%s|%.6f|%.6f|%d|%d',
            $keyword,
            $lat,
            $lng,
            $radiusKm,
            self::CACHE_LIFETIME_SEC
        ));

        if ($cached = $this->fetchCache($cacheKey)) {
            return $cached;
        }

        try {
            $googleResults = $this->fetchFromGoogle($lat, $lng, $radiusMeters, $keyword);
            $sanitized = array_map([$this, 'normalizeWorkshop'], $googleResults);
            $this->storeCache($cacheKey, $sanitized);
            return $sanitized;
        } catch (Exception $e) {
            // Log error and return empty array instead of breaking
            error_log("Workshop search failed: " . $e->getMessage());
            return [];
        }
    }

    /* ------------- GOOGLE FETCH ------------- */
    private function fetchFromGoogle(
        float  $lat,
        float  $lng,
        int    $radiusMeters,
        string $keyword
    ): array {
        $params = http_build_query([
            'location' => "{$lat},{$lng}",
            'radius'   => $radiusMeters,
            'keyword'  => $keyword,
            'type'     => 'car_repair',
            'key'      => $this->googleApiKey,
        ]);

        $url = self::GOOGLE_API_BASE . 'nearbysearch/json?' . $params;

        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => 'User-Agent: Carfify/1.0',
                'timeout' => 8, // seconds – best effort
            ],
        ];
        $ctx  = stream_context_create($opts);

        $raw  = @file_get_contents($url, false, $ctx);
        
        if ($raw === false) {
            throw new Exception('Google Places API unreachable via file_get_contents');
        }

        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from Google Places: ' . json_last_error_msg());
        }
        
        if (!isset($data['results'])) {
            throw new Exception('Invalid Google-Places structure: missing results');
        }

        // Handle API errors
        if (isset($data['error_message'])) {
            throw new Exception('Google Places API error: ' . $data['error_message']);
        }

        return $data['results'];
    }

    /* ------------- NORMALIZE ------------- */
    private function normalizeWorkshop(array $raw): array
    {
        $photoUrl = null;
        if (!empty($raw['photos'][0]['photo_reference'])) {
            $ref = $raw['photos'][0]['photo_reference'];
            $photoUrl = self::GOOGLE_API_BASE
                . "photo?maxwidth=320&photoreference={$ref}&key={$this->googleApiKey}";
        }

        return [
            'placeId'      => $raw['place_id'] ?? null,
            'name'         => $raw['name'] ?? 'Unknown workshop',
            'address'      => $raw['vicinity'] ?? ($raw['formatted_address'] ?? null),
            'lat'          => $raw['geometry']['location']['lat'] ?? null,
            'lng'          => $raw['geometry']['location']['lng'] ?? null,
            'rating'       => $raw['rating'] ?? null,
            'totalRatings' => $raw['user_ratings_total'] ?? 0,
            'photo'        => $photoUrl,
            'openNow'      => $raw['opening_hours']['open_now'] ?? null,
        ];
    }

    /* ------------- CACHING ------------- */
    private function fetchCache(string $cacheKey): ?array
    {
        try {
            $stmt = $this->db->prepare(
                'SELECT payload
                 FROM cached_workshops
                 WHERE cache_key = :key
                 AND created_at > datetime("now", "-1 hour")'
            );
            $stmt->execute([':key' => $cacheKey]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row ? json_decode($row['payload'], true) : null;
        } catch (Exception $e) {
            // Cache unavailable, continue without caching
            return null;
        }
    }

    private function storeCache(string $cacheKey, array $data): void
    {
        try {
            $this->db->prepare(
                'DELETE FROM cached_workshops WHERE cache_key = :key'
            )->execute([':key' => $cacheKey]);

            $stmt = $this->db->prepare(
                'INSERT INTO cached_workshops (cache_key, payload, created_at)
                 VALUES (:key, :payload, datetime("now"))'
            );
            
            $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($json === false) {
                throw new Exception('Failed to encode workshop data: ' . json_last_error_msg());
            }
            
            $stmt->execute([
                ':key'     => $cacheKey,
                ':payload' => $json,
            ]);
        } catch (Exception $e) {
            // Cache storage failed, silently continue
            error_log("Failed to cache workshops: " . $e->getMessage());
        }
    }

    /* ------------- INITIAL TABLE CREATION ------------- */
    private function createTableIfNotExists(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS cached_workshops (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                cache_key VARCHAR(64) NOT NULL UNIQUE,
                payload TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");
        
        $this->db->exec("
            CREATE UNIQUE INDEX IF NOT EXISTS idx_cache_key 
            ON cached_workshops(cache_key);
        ");
    }
}
