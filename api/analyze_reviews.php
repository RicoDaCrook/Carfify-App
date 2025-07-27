<?php
/**
 * analyze_reviews.php
 * Endpunkt zur Bewertungsanalyse einer Werkstatt.
 * Erwartet POST-JSON mit place_id.
 * Gibt zusammengefasste Pro/Contra-Liste zurück (Google Gemini simuliert).
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

$place_id = $input['place_id'] ?? '';
if (!$place_id) {
    http_response_code(400);
    echo json_encode(['error' => 'place_id erforderlich.']);
    exit();
}

// Simulierte Bewertungen
$reviews = simulateGoogleReviews($place_id);

// Simulierter Gemini-Aufruf zur Zusammenfassung
$summary = summarizeReviewsWithGemini($reviews);

echo json_encode($summary);

/**
 * Simuliert Google Places Details (Reviews)
 * @param string $place_id
 * @return array
 */
function simulateGoogleReviews(string $place_id): array
{
    $dummyReviews = [
        'w1' => [
            ['text' => 'Sehr freundlich und schnell. Preis-Leistung top!'],
            ['text' => 'Habe meine Bremsen hier machen lassen, alles super erklärt.'],
            ['text' => 'Terminvergabe etwas kompliziert, aber sonst gut.'],
            ['text' => 'Teurer als erwartet, aber Qualität stimmt.'],
        ],
        'w2' => [
            ['text' => 'Günstig und schnell, aber etwas chaotisch.'],
            ['text' => 'Nettes Personal, habe mich gut aufgehoben gefühlt.'],
            ['text' => 'Wartezeit war ok, Ergebnis auch.'],
        ],
        'w3' => [
            ['text' => 'Absolute Spezialisten, sehr kompetent.'],
            ['text' => 'Preis hoch, aber man bekommt was man zahlt.'],
            ['text' => 'Termin war schwer zu bekommen, aber es hat sich gelohnt.'],
        ]
    ];
    return $dummyReviews[$place_id] ?? [];
}

/**
 * Simuliert Google Gemini Zusammenfassung
 * @param array $reviews
 * @return array
 */
function summarizeReviewsWithGemini(array $reviews): array
{
    // Vereinfachte Logik
    $pro = [];
    $con = [];

    foreach ($reviews as $r) {
        $txt = strtolower($r['text']);
        if (strpos($txt, 'freundlich') !== false || strpos($txt, 'kompetent') !== false) {
            $pro[] = 'Freundliches und kompetentes Personal';
        }
        if (strpos($txt, 'günstig') !== false || strpos($txt, 'preis') !== false) {
            if (strpos($txt, 'teuer') !== false) {
                $con[] = 'Etwas teurer als erwartet';
            } else {
                $pro[] = 'Gutes Preis-Leistungs-Verhältnis';
            }
        }
        if (strpos($txt, 'termin') !== false && strpos($txt, 'schwer') !== false) {
            $con[] = 'Terminvereinbarung kann schwierig sein';
        }
    }

    // Duplikate entfernen
    $pro = array_values(array_unique($pro));
    $con = array_values(array_unique($con));

    return [
        'pros' => $pro,
        'cons' => $con
    ];
}