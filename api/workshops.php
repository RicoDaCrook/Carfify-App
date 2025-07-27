<?php
/**
 * workshops.php
 * Endpunkt zur Suche nach passenden Werkstätten.
 * Erwartet POST-JSON mit latitude, longitude, category.
 * Gibt gefilterte Werkstattliste zurück.
 */

header('Content-Type: application/json; charset=utf-8');

// CORS erlauben
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// OPTIONS-Request sofort beantworten
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Nur POST erlauben
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Nur POST erlaubt.']);
    exit();
}

// JSON-Body einlesen
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['error' => 'Ungültiges JSON.']);
    exit();
}

$lat = $input['latitude'] ?? null;
$lng = $input['longitude'] ?? null;
$category = strtolower(trim($input['category'] ?? ''));

if (!$lat || !$lng) {
    http_response_code(400);
    echo json_encode(['error' => 'latitude und longitude erforderlich.']);
    exit();
}

// Simulierter Google Maps Places API-Aufruf
$workshops = simulateGoogleMapsNearby($lat, $lng, $category);

// Intelligente Filterung: Spezialisten nur bei Bedarf
$filtered = filterWorkshops($workshops, $category);

echo json_encode($filtered);

/**
 * Simuliert Google Maps Places Nearby Search
 * @param float $lat
 * @param float $lng
 * @param string $category
 * @return array
 */
function simulateGoogleMapsNearby(float $lat, float $lng, string $category): array
{
    // Dummy-Daten
    $baseWorkshops = [
        [
            'place_id' => 'w1',
            'name' => 'MeisterWerk Kfz-Werkstatt',
            'vicinity' => 'Musterstraße 12, 10115 Berlin',
            'geometry' => ['location' => ['lat' => $lat + 0.01, 'lng' => $lng + 0.01]],
            'rating' => 4.6,
            'user_ratings_total' => 120,
            'types' => ['car_repair', 'car_dealer'],
            'specialties' => ['Bremsen', 'Motor'],
            'price_level' => 2
        ],
        [
            'place_id' => 'w2',
            'name' => 'Speedy Autoservice',
            'vicinity' => 'Beispielweg 5, 10117 Berlin',
            'geometry' => ['location' => ['lat' => $lat - 0.005, 'lng' => $lng + 0.02]],
            'rating' => 4.3,
            'user_ratings_total' => 88,
            'types' => ['car_repair'],
            'specialties' => ['Elektrik'],
            'price_level' => 1
        ],
        [
            'place_id' => 'w3',
            'name' => 'Premium Getriebe Spezialist',
            'vicinity' => 'Luxusallee 99, 10119 Berlin',
            'geometry' => ['location' => ['lat' => $lat + 0.015, 'lng' => $lng - 0.01]],
            'rating' => 4.9,
            'user_ratings_total' => 45,
            'types' => ['car_repair'],
            'specialties' => ['Getriebe'],
            'price_level' => 3
        ]
    ];
    return $baseWorkshops;
}

/**
 * Filtert Werkstätten nach Relevanz
 * @param array $workshops
 * @param string $category
 * @return array
 */
function filterWorkshops(array $workshops, string $category): array
{
    // Standard-Kategorien: Bremsen, Motor, Elektrik, Getriebe
    $standardCats = ['bremsen', 'motor', 'elektrik'];
    $isStandard = in_array($category, $standardCats);

    $filtered = [];
    foreach ($workshops as $w) {
        $specialties = array_map('strtolower', $w['specialties']);
        if ($isStandard) {
            // Bei Standard-Kategorien: Alle Werkstätten anzeigen
            $filtered[] = $w;
        } else {
            // Bei Spezial-Kategorien: Nur passende Spezialisten
            if (in_array($category, $specialties)) {
                $filtered[] = $w;
            }
        }
    }

    // Nach Bewertung sortieren
    usort($filtered, function ($a, $b) {
        return $b['rating'] <=> $a['rating'];
    });

    return $filtered;
}