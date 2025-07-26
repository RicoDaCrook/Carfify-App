<?php
// Carfify Konfiguration

// Datenbank-Konfiguration
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'carfify';
$user = getenv('DB_USER') ?: 'postgres';
$password = getenv('DB_PASSWORD') ?: '';

// API-Schlüssel
$claude_api_key = getenv('CLAUDE_API_KEY');
$google_maps_api_key = getenv('GOOGLE_MAPS_API_KEY');
$gemini_api_key = getenv('GEMINI_API_KEY');

// NEUE API-Schlüssel für Verkaufs-Feature
define('IMAGE_ANALYSIS_API_KEY', getenv('IMAGE_ANALYSIS_API_KEY'));
define('MARKET_ANALYSIS_API_KEY', getenv('MARKET_ANALYSIS_API_KEY'));

// Datenbank-Verbindung
$dsn = "pgsql:host=$host;dbname=$dbname";
try {
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die('Datenbankverbindung fehlgeschlagen: ' . $e->getMessage());
}

// Sitzungsstart
session_start();

// CORS-Header für API
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Content-Type
header('Content-Type: application/json; charset=utf-8');

// Fehlerbehandlung
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
});

// Helper-Funktionen
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// Logging-Funktion
function logError($message, $context = []) {
    error_log(date('Y-m-d H:i:s') . ' - ' . $message . ' - ' . json_encode($context));
}