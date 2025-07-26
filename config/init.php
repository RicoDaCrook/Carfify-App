<?php
// Carfify Konfiguration
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Datenbankverbindung
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'carfify';
$user = getenv('DB_USER') ?: 'postgres';
$password = getenv('DB_PASSWORD') ?: 'password';

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Datenbankverbindung fehlgeschlagen']);
    exit();
}

// API-Schlüssel
define('CLAUDE_API_KEY', getenv('CLAUDE_API_KEY'));
define('GOOGLE_MAPS_API_KEY', getenv('GOOGLE_MAPS_API_KEY'));
define('GEMINI_API_KEY', getenv('GEMINI_API_KEY'));
// NEUE API-Schlüssel für Verkaufs-Feature
define('IMAGE_ANALYSIS_API_KEY', getenv('IMAGE_ANALYSIS_API_KEY'));
define('MARKET_ANALYSIS_API_KEY', getenv('MARKET_ANALYSIS_API_KEY'));

// Fehlerbehandlung
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
});

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR])) {
        http_response_code(500);
        echo json_encode(['error' => 'Interner Serverfehler']);
    }
});
?>