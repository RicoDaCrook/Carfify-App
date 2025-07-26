<?php
require_once '../config/init.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    estimatePrice($_POST);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Methode nicht erlaubt']);
}

function estimatePrice($data) {
    // Validierung der Eingaben
    $vehicleId = intval($data['vehicle_id'] ?? 0);
    $mileage = intval($data['mileage'] ?? 0);
    $condition = json_decode($data['condition'] ?? '{}', true);
    $imageAnalysis = json_decode($data['image_analysis'] ?? '{}', true);
    
    if (!$vehicleId || $mileage <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Ungültige Eingaben']);
        return;
    }
    
    // Simulierte Marktanalyse-API
    // In Produktion würde hier die Mobile.de API oder ähnliches aufgerufen
    $basePrice = 15000; // Basispreis für Demo
    
    // Kilometerstand anpassen
    $mileageFactor = max(0.5, 1 - ($mileage / 200000));
    
    // Zustand anpassen
    $conditionFactor = 1;
    if (isset($condition['accident_free']) && !$condition['accident_free']) {
        $conditionFactor *= 0.85;
    }
    if (isset($condition['service_history']) && $condition['service_history']) {
        $conditionFactor *= 1.1;
    }
    
    // Bildanalyse berücksichtigen
    if (isset($imageAnalysis['damages'])) {
        $damageCount = count($imageAnalysis['damages']);
        $conditionFactor *= max(0.7, 1 - ($damageCount * 0.05));
    }
    
    $estimatedPrice = $basePrice * $mileageFactor * $conditionFactor;
    $minPrice = $estimatedPrice * 0.9;
    $maxPrice = $estimatedPrice * 1.1;
    
    $response = [
        'estimated_price' => [
            'min' => round($minPrice, -2),
            'max' => round($maxPrice, -2),
            'average' => round($estimatedPrice, -2)
        ],
        'market_comparison' => 'Basierend auf 47 vergleichbaren Angeboten',
        'factors' => [
            'mileage' => $mileage,
            'condition' => $condition,
            'damages' => $imageAnalysis['damages'] ?? []
        ]
    ];
    
    echo json_encode($response);
}
?>