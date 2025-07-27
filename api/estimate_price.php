<?php
/**
 * API-Endpunkt zur Preisschätzung für Fahrzeugverkäufe
 */
require_once '../config/database.php';
require_once '../classes/Auth.php';

header('Content-Type: application/json');

// Authentifizierung prüfen
$auth = new Auth($pdo);
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Nicht autorisiert']);
    exit;
}

// Nur POST-Anfragen erlauben
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Methode nicht erlaubt']);
    exit;
}

// Eingabedaten validieren
$data = json_decode(file_get_contents('php://input'), true);

$requiredFields = ['make', 'model', 'year', 'mileage', 'condition', 'photos'];
foreach ($requiredFields as $field) {
    if (!isset($data[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Feld '$field' fehlt"]);
        exit;
    }
}

// Basispreis basierend auf Marktdaten (simuliert)
function getBasePrice($make, $model, $year, $mileage) {
    // Simulierte Marktdatenbank
    $marketPrices = [
        'VW' => ['Golf' => ['2020' => 25000, '2021' => 27000, '2022' => 29000]],
        'BMW' => ['3er' => ['2020' => 35000, '2021' => 38000, '2022' => 41000]],
        'Mercedes' => ['C-Klasse' => ['2020' => 37000, '2021' => 40000, '2022' => 43000]]
    ];

    $basePrice = $marketPrices[$make][$model][$year] ?? 20000;
    
    // Kilometerstand berücksichtigen
    $mileageFactor = max(0.5, 1 - ($mileage / 200000));
    
    return $basePrice * $mileageFactor;
}

// Zustandsfaktoren
$conditionFactors = [
    'excellent' => 1.1,
    'good' => 1.0,
    'fair' => 0.85,
    'poor' => 0.7
];

// Bildanalyse simulieren
function analyzePhotos($photos) {
    // Simulierte Bildanalyse
    $damageScore = 0;
    $photoQuality = 0;
    
    foreach ($photos as $photo) {
        // Prüfe ob Base64-String vorhanden
        if (preg_match('/^data:image\/\w+;base64,/', $photo)) {
            $photoQuality += 10;
            // Simulierte Schadenserkennung
            if (strlen($photo) > 100000) { // Größere Bilder = mehr Details
                $damageScore += rand(0, 5);
            }
        }
    }
    
    return [
        'damage_detected' => $damageScore > 10,
        'photo_quality' => min(100, $photoQuality),
        'damage_factor' => max(0.9, 1 - ($damageScore / 100))
    ];
}

// Preis berechnen
$basePrice = getBasePrice($data['make'], $data['model'], $data['year'], $data['mileage']);
$conditionFactor = $conditionFactors[$data['condition']] ?? 1.0;
$photoAnalysis = analyzePhotos($data['photos']);

$estimatedPrice = round($basePrice * $conditionFactor * $photoAnalysis['damage_factor'], -2);

// Preisspanne berechnen
$minPrice = round($estimatedPrice * 0.9, -2);
$maxPrice = round($estimatedPrice * 1.1, -2);

// Antwort vorbereiten
$response = [
    'estimated_price' => $estimatedPrice,
    'price_range' => [
        'min' => $minPrice,
        'max' => $maxPrice
    ],
    'analysis' => [
        'base_price' => round($basePrice, -2),
        'condition_factor' => $conditionFactor,
        'damage_factor' => $photoAnalysis['damage_factor'],
        'photo_quality' => $photoAnalysis['photo_quality']
    ],
    'recommendations' => []
];

// Empfehlungen basierend auf Analyse
if ($photoAnalysis['damage_detected']) {
    $response['recommendations'][] = 'Kleine Reparaturen könnten den Wert erhöhen';
}

if ($photoAnalysis['photo_quality'] < 50) {
    $response['recommendations'][] = 'Bessere Fotos würden eine genauere Bewertung ermöglichen';
}

if ($data['mileage'] > 150000) {
    $response['recommendations'][] = 'Eine Inspektion könnte das Vertrauen der Käufer stärken';
}

echo json_encode($response);
