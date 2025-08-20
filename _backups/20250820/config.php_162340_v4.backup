<?php
/**
 * CARFIFY AI v4.0 - Configuration
 * Standalone-Version ohne Composer
 */

// Fehlerbehandlung aktivieren
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Zeitzone setzen
date_default_timezone_set('Europe/Berlin');

// Basis-Konfiguration
$config = [
    'app' => [
        'name' => 'Carfify AI',
        'version' => '4.0.0',
        'debug' => true,
        'environment' => 'development'
    ],
    
    'database' => [
        'host' => 'localhost',
        'name' => 'carfify',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4'
    ],
    
    'paths' => [
        'base' => dirname(__FILE__),
        'uploads' => dirname(__FILE__) . '/uploads',
        'logs' => dirname(__FILE__) . '/logs',
        'cache' => dirname(__FILE__) . '/cache'
    ],
    
    'security' => [
        'salt' => 'carfify_secure_salt_2024',
        'session_timeout' => 3600
    ],
    
    'api' => [
        'openai_key' => '',
        'anthropic_key' => '',
        'rate_limit' => 100
    ]
];

// Datenbank-Verbindung
function getDBConnection() {
    global $config;
    try {
        $dsn = "mysql:host={$config['database']['host']};dbname={$config['database']['name']};charset={$config['database']['charset']}";
        $pdo = new PDO($dsn, $config['database']['user'], $config['database']['pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch(PDOException $e) {
        die("Datenbankverbindung fehlgeschlagen: " . $e->getMessage());
    }
}

// Session starten
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Autoloader für eigene Klassen (ohne Composer)
spl_autoload_register(function($className) {
    $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
    $file = __DIR__ . '/classes/' . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Composer-Autoload auskommentiert für Standalone-Betrieb
// require_once __DIR__ . '/vendor/autoload.php';

// Hilfsfunktionen
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function logError($message) {
    global $config;
    $logFile = $config['paths']['logs'] . '/error_' . date('Y-m-d') . '.log';
    $message = date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL;
    file_put_contents($logFile, $message, FILE_APPEND | LOCK_EX);
}

// Konfiguration global verfügbar machen
$GLOBALS['config'] = $config;
?>