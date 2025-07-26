<?php
require_once '../config/init.php';

// POST-Preisschätzung für Verkauf
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Methode nicht erlaubt']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

// Validierung
$required = ['vehicle_id', 'mileage', 'condition_report', 'images_analysis'];
foreach ($required as $field) {
    if (!isset($input[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Feld $field fehlt"]);
        exit();
    }
}

try {
    // Fahrzeug abrufen
    $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
    $stmt->execute([(int)$input['vehicle_id']]);
    $vehicle = $stmt->fetch();

    if (!$vehicle) {
        throw new Exception('Fahrzeug nicht gefunden');
    }

    // Simulierte Marktanalyse-API
    // In der echten Implementierung würden wir Mobile.de API nutzen
    $market_data = [
        'average_price' => 15000,
        'min_price' => 12000,
        'max_price' => 18000,
        'comparable_vehicles' => 45,
        'market_trend' => 'stable'
    ];

    // KI-basierte Preisberechnung
    $base_price = $market_data['average_price'];
    
    // Kilometer-Abzug
    $mileage_factor = max(0.5, 1 - ($input['mileage'] / 200000));
    
    // Zustandsfaktor
    $condition = json_decode($input['condition_report'], true);
    $condition_factor = 1;
    if (isset($condition['accident_free']) && !$condition['accident_free']) {
        $condition_factor *= 0.85;
    }
    if (isset($condition['service_history']) && $condition['service_history']) {
        $condition_factor *= 1.1;
    }

    // Bildanalyse-Faktor
    $damage_factor = 1;
    if (isset($input['images_analysis']['damages'])) {
        $damage_count = count($input['images_analysis']['damages']);
        $damage_factor = max(0.7, 1 - ($damage_count * 0.05));
    }

    // Endpreis berechnen
    $estimated_price = round($base_price * $mileage_factor * $condition_factor * $damage_factor, 2);
    
    // Preisspanne
    $min_price = round($estimated_price * 0.9, 2);
    $max_price = round($estimated_price * 1.1, 2);

    echo json_encode([
        'success' => true,
        'estimated_price' => $estimated_price,
        'price_range' => [
            'min' => $min_price,
            'max' => $max_price
        ],
        'factors' => [
            'mileage_factor' => $mileage_factor,
            'condition_factor' => $condition_factor,
            'damage_factor' => $damage_factor
        ],
        'market_data' => $market_data
    ]);

} catch (Exception $e) {
    logError('Preisschätzung Fehler', ['error' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode(['error' => 'Preis konnte nicht geschätzt werden']);
}