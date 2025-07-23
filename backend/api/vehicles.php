<?php
/**
 * Carfify – Backend-API Endpunkt
 * vehicles.php
 *
 * Liefert eine Liste aller unterstützten Fahrzeuge.
 * Wird über Vercel-Funktion /api/vehicles aufgerufen.
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Content-Type: application/json; charset=utf-8');

$data = [
    [
        'id' => 1,
        'brand' => 'VW',
        'model' => 'Golf',
        'year' => 2017,
        'engine' => ['petrol', 'TFSI', '1.4', '92kW']
    ],
    [
        'id' => 2,
        'brand' => 'BMW',
        'model' => '320i',
        'year' => 2019,
        'engine' => ['petrol', 'B48', '2.0', '135kW']
    ],
    [
        'id' => 3,
        'brand' => 'Tesla',
        'model' => 'Model 3',
        'year' => 2021,
        'engine' => ['electric', 'RWD', '60kWh', '211kW']
    ]
];

http_response_code(200);
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
