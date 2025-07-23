<?php
/**
 * Carfify - Fahrzeugdaten API
 * 
 * Helfer-API zur Abfrage von Fahrzeugdaten basierend auf HSN/TSN
 * Rückgabe: JSON-Array mit passenden Fahrzeugen
 * 
 * Holt Daten aus der PostgreSQL-Datenbank
 */

// Konstanten definieren
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../security/cors.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Vehicle.php';

// Content-Type und CORS-Header
header('Content-Type: application/json; charset=utf-8');

// Degressive Rate Limiting (max 5 Anfragen pro Sekunde pro IP)
$redis = new Redis();
if ($redis->connect('127.0.0.1', 6379)) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $key = "rate_limit:vehicles:$ip";
    $current = $redis->get($key);
    
    if ($current && $current >= 5) {
        http_response_code(429);
        echo json_encode(['error' => 'Rate limit exceeded', 'retry_after' => 1]);
        exit;
    }
    
    $redis->multi()
         ->incr($key)
         ->expire($key, 1)
         ->exec();
}

// Eingabe validieren
$params = [
    'hsn' => FILTER_SANITIZE_STRING,
    'tsn' => FILTER_SANITIZE_STRING,
    'make' => FILTER_SANITIZE_STRING,
    'model' => FILTER_SANITIZE_STRING,
    'year' => FILTER_VALIDATE_INT,
    'limit' => [
        'filter' => FILTER_VALIDATE_INT,
        'options' => [
            'default' => 10,
            'min_range' => 1,
            'max_range' => 100
        ]
    ]
];

$input = filter_input_array(INPUT_GET, $params);

try {
    // Datenbankverbindung aufbauen
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $vehicle = new Vehicle($pdo);
    
    // Cache-Prüfung (Redis)
    $cacheKey = 'vehicles:' . md5(json_encode($input));
    if ($redis->isConnected() && ($cache = $redis->get($cacheKey))) {
        echo $cache;
        exit;
    }
    
    // Fahrzeuge suchen
    $results = $vehicle->search($input);
    
    // Ergebnis aufbereiten
    $response = [
        'timestamp' => date('c'),
        'count' => count($results),
        'vehicles' => $results,
        'query' => $input
    ];
    
    $jsonResponse = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
    // In Cache speichern (15 Minuten)
    if ($redis->isConnected()) {
        $redis->setEx($cacheKey, 900, $jsonResponse);
    }
    
    echo $jsonResponse;
    
} catch (PDOException $e) {
    // Fehlerbehandlung
    http_response_code(500);
    echo json_encode([
        'error' => 'Database connection failed',
        'message' => $_SERVER['DEBUG'] ? $e->getMessage() : 'Internal server error'
    ]);
    
    // Fehler loggen
    error_log('Vehicle search error: ' . $e->getMessage(), 0);
}
