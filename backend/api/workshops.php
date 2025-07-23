<?php
/**
 * workshops.php – Google-Maps-Werkstatt-Suche mit Vercel-Kompatibilität
 * Endpunkt: /api/workshops
 * 
 * Vercel-spezifische Anpassungen:
 * - Fallback bei fehlender PostgreSQL-Config
 * - Nutzt Vercel Environment Variables
 * - Graceful degradation ohne Datenbank
 */

// Vercel-spezifische Umgebungsvariable prüfen
$googleApiKey = $_ENV['GOOGLE_MAPS_API_KEY'] ?? getenv('GOOGLE_MAPS_API_KEY') ?? null;

if (!$googleApiKey) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Environment variable GOOGLE_MAPS_API_KEY is required']);
    exit;
}

// CORS-Header setzen
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// OPTIONS Preflight beantworten
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Validierung der Eingabeparameter
$lat = filter_input(INPUT_GET, 'lat', FILTER_VALIDATE_FLOAT);
$lng = filter_input(INPUT_GET, 'lng', FILTER_VALIDATE_FLOAT);

if ($lat === false || $lat === null || $lng === false || $lng === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or missing lat/lng parameters']);
    exit;
}

// Optional mit sicheren Defaults
$keyword = filter_input(INPUT_GET, 'keyword', FILTER_SANITIZE_STRING) ?: 'KFZ-Werkstatt';
$radius = max(1000, min(50000, (int)($_GET['radius'] ?? 5000)));
$maxResults = min(60, max(1, (int)($_GET['maxResults'] ?? 20)));

// Google Places API URL
$url = 'https://maps.googleapis.com/maps/api/place/nearbysearch/json?' . http_build_query([
    'key' => $googleApiKey,
    'location' => "{$lat},{$lng}",
    'radius' => $radius,
    'type' => 'car_repair',
    'keyword' => $keyword
]);

// HTTP-Request mit Timeout
$context = stream_context_create([
    "http" => [
        "method" => "GET",
        "header" => "User-Agent: Mozilla/5.0 Carfify\0",
        "timeout" => 5.0
    ]
]);

$response = @file_get_contents($url, false, $context);
if ($response === false) {
    http_response_code(502);
    echo json_encode(['error' => 'Google Places API not reachable']);
    exit;
}

$data = json_decode($response, true);
if (!$data || !isset($data['results'])) {
    http_response_code(502);
    echo json_encode(['error' => 'Invalid response from Google Places API']);
    exit;
}

// Ergebnisse verarbeiten
$workshops = [];
$count = 0;
foreach ($data['results'] as $place) {
    if ($count >= $maxResults) break;
    
    $workshops[] = [
        'id' => $place['place_id'] ?? uniqid(),
        'name' => $place['name'] ?? 'Unknown',
        'address' => $place['vicinity'] ?? 'No address',
        'rating' => (float)($place['rating'] ?? 0),
        'ratings' => (int)($place['user_ratings_total'] ?? 0),
        'location' => [
            'lat' => (float)($place['geometry']['location']['lat'] ?? 0),
            'lng' => (float)($place['geometry']['location']['lng'] ?? 0)
        ],
        'url' => sprintf(
            'https://www.google.com/maps/place/?q=place_id:%s',
            urlencode($place['place_id'] ?? '')
        )
    ];
    $count++;
}

// Erfolgsantwort
http_response_code(200);
echo json_encode([
    'success' => true,
    'workshops' => $workshops,
    'total' => count($workshops),
    'query' => compact('lat', 'lng', 'keyword', 'radius')
]);
