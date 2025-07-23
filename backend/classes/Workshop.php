<?php
/**
 * Workshop.php – Google-Maps-cached workshop API for Carfify
 * Fetches workshops via Places API, caches results hourly, anonymized (!PII)
 * Designed for Vercel/lambda-php compatibility (cURL fallback, env vars).
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
    private const HTTP_TIMEOUT_SEC   = 8;         // Lambda-max 10; set 8 to be safe

    /* ------------- PRIVATE ------------- */
    private PDO    $db;
    private string $googleApiKey;

    /* ------------- CONSTRUCTOR ------------- */
    public function __construct(PDO $db)
    {
        $this->db = $db;

        // Read key from ENV — safer than hard-coding
        $key = getenv('CF_GOOGLE_API_KEY') ?: $_ENV['CF_GOOGLE_API_KEY'] ?? false;
        if (!$key) {
            throw new Exception('CF_GOOGLE_API_KEY is missing from environment');
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

        $googleResults = $this->fetchFromGoogle($lat, $lng, $radiusMeters, $keyword);

        $sanitized = array_map([$this, 'normalizeWorkshop'], $googleResults);
        $this->storeCache($cacheKey, $sanitized);

        return $sanitized;
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

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::HTTP_TIMEOUT_SEC,
            CURLOPT_USERAGENT      => 'Carfify/1.0',
            CURLOPT_FAILONERROR    => true,
        ]);

        $json = curl_exec($ch);
        if ($json === false) {
            throw new Exception('Google API unreachable: ' . curl_error($ch));
        }
        curl_close($ch);

        $data = json_decode($json, true);
        if (!isset($data['results']) || json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid Google-Places response.');
        }

        return $data['results'];
    }

    /* ------------- NORMALIZATION ------------- */
    private function normalizeWorkshop(array $raw): array
    {
        $photoUrl = null;
        if (!empty($raw['photos'][0])) {
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

    private function storeCache(string $cacheKey, array $data): void
    {
        $this->db->prepare(
            'DELETE FROM cached_workshops WHERE cache_key = :key'
        )->execute([':key' => $cacheKey]);

        $this->db->prepare(
            'INSERT INTO cached_workshops (cache_key, payload, created_at)
             VALUES (:key, :payload, NOW())'
        )->execute([
            ':key'     => $cacheKey,
            ':payload' => json_encode($data,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ]);
    }
}

