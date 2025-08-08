<?php
/**
 * Carfify v4.0 Configuration
 * Haupt-Konfigurationsdatei für alle Features
 */

// Sicherheits-Check
if (!defined('CARFIFY_START')) {
    die('Direct access not allowed');
}

// Basis-Konfiguration
define('CARFIFY_VERSION', '4.0.0');
define('CARFIFY_ENV', 'production'); // 'development' oder 'production'

// Datenbank-Konfiguration
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'carfify_db');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_CHARSET', 'utf8mb4');

// PWA-Konfiguration
define('PWA_NAME', 'Carfify');
define('PWA_SHORT_NAME', 'Carfify');
define('PWA_DESCRIPTION', 'Auto Diagnose & Verkauf');
define('PWA_THEME_COLOR', '#1a1a2e');
define('PWA_BACKGROUND_COLOR', '#ffffff');

// API-Konfiguration
define('API_BASE_URL', $_ENV['API_BASE_URL'] ?? 'https://api.carfify.local');
define('API_TIMEOUT', 30);

// Diagnose-Feature
define('DIAG_MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('DIAG_ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/webp']);

// Verkaufs-Feature
define('MAX_IMAGES_PER_CAR', 10);
define('IMAGE_QUALITY', 85);

// Session-Konfiguration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));

// Error-Handling
if (CARFIFY_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Zeitzone
date_default_timezone_set('Europe/Berlin');

// Hilfsfunktionen
function carfify_base_url() {
    return (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
}

function carfify_asset($path) {
    return carfify_base_url() . '/assets/' . ltrim($path, '/');
}

// Datenbank-Verbindung
function getDbConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }
    
    return $pdo;
}

// Sicherheits-Token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Session-Start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Konstante für Sicherheits-Check
define('CARFIFY_START', true);
?>