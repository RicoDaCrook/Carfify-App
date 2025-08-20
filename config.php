<?php
/**
 * CARFIFY Configuration v4.0
 * Haupt-Konfigurationsdatei für das Carfify System
 * 
 * Letzte Änderung: 2024-01-15
 * Version: 4.0.1
 */

// ===========================================
// BASIS-KONFIGURATION
// ===========================================

// Umgebung festlegen
if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'production'); // production | development | testing
}

// Basis-URLs
if (!defined('BASE_URL')) {
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        $protocol = 'https://';
    } else {
        $protocol = 'http://';
    }
    
    $host = $_SERVER['HTTP_HOST'];
    $script = $_SERVER['SCRIPT_NAME'];
    $path = trim(dirname($script), '/');
    
    define('BASE_URL', $protocol . $host . '/' . $path . '/');
}

// Root-Pfade
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', realpath(dirname(__FILE__) . '/../') . '/');
}

if (!defined('APP_PATH')) {
    define('APP_PATH', ROOT_PATH . 'app/');
}

if (!defined('PUBLIC_PATH')) {
    define('PUBLIC_PATH', ROOT_PATH . 'public/');
}

// ===========================================
// DATENBANK-KONFIGURATION
// ===========================================

if (!defined('DB_HOST')) {
    define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
}

if (!defined('DB_NAME')) {
    define('DB_NAME', $_ENV['DB_NAME'] ?? 'carfify_db');
}

if (!defined('DB_USER')) {
    define('DB_USER', $_ENV['DB_USER'] ?? 'root');
}

if (!defined('DB_PASS')) {
    define('DB_PASS', $_ENV['DB_PASS'] ?? '');
}

if (!defined('DB_CHARSET')) {
    define('DB_CHARSET', 'utf8mb4');
}

// ===========================================
// SICHERHEIT
// ===========================================

if (!defined('ENCRYPTION_KEY')) {
    define('ENCRYPTION_KEY', $_ENV['ENCRYPTION_KEY'] ?? 'your-default-encryption-key-change-this');
}

if (!defined('JWT_SECRET')) {
    define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? 'your-jwt-secret-change-this');
}

if (!defined('SESSION_TIMEOUT')) {
    define('SESSION_TIMEOUT', 3600); // 1 Stunde
}

// ===========================================
// API-KONFIGURATION
// ===========================================

if (!defined('API_VERSION')) {
    define('API_VERSION', 'v1');
}

if (!defined('API_RATE_LIMIT')) {
    define('API_RATE_LIMIT', 100); // Anfragen pro Minute
}

// ===========================================
// UPLOAD-KONFIGURATION
// ===========================================

if (!defined('MAX_UPLOAD_SIZE')) {
    define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB
}

if (!defined('UPLOAD_PATH')) {
    define('UPLOAD_PATH', PUBLIC_PATH . 'uploads/');
}

if (!defined('ALLOWED_IMAGE_TYPES')) {
    define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
}

// ===========================================
// CACHE-KONFIGURATION
// ===========================================

if (!defined('CACHE_ENABLED')) {
    define('CACHE_ENABLED', ENVIRONMENT === 'production');
}

if (!defined('CACHE_DRIVER')) {
    define('CACHE_DRIVER', 'file'); // file | redis | memcached
}

if (!defined('CACHE_TTL')) {
    define('CACHE_TTL', 3600); // 1 Stunde
}

// ===========================================
// LOGGING-KONFIGURATION
// ===========================================

if (!defined('LOG_LEVEL')) {
    define('LOG_LEVEL', ENVIRONMENT === 'production' ? 'error' : 'debug');
}

if (!defined('LOG_PATH')) {
    define('LOG_PATH', ROOT_PATH . 'logs/');
}

if (!defined('LOG_FILE')) {
    define('LOG_FILE', LOG_PATH . 'carfify_' . date('Y-m-d') . '.log');
}

// ===========================================
// E-MAIL-KONFIGURATION
// ===========================================

if (!defined('SMTP_HOST')) {
    define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? 'localhost');
}

if (!defined('SMTP_PORT')) {
    define('SMTP_PORT', $_ENV['SMTP_PORT'] ?? 587);
}

if (!defined('SMTP_USER')) {
    define('SMTP_USER', $_ENV['SMTP_USER'] ?? '');
}

if (!defined('SMTP_PASS')) {
    define('SMTP_PASS', $_ENV['SMTP_PASS'] ?? '');
}

if (!defined('SMTP_FROM')) {
    define('SMTP_FROM', $_ENV['SMTP_FROM'] ?? 'noreply@carfify.de');
}

// ===========================================
// RECHERCHE-APIs
// ===========================================

if (!defined('SCHWACKE_API_KEY')) {
    define('SCHWACKE_API_KEY', $_ENV['SCHWACKE_API_KEY'] ?? '');
}

if (!defined('AUTODAT_API_KEY')) {
    define('AUTODAT_API_KEY', $_ENV['AUTODAT_API_KEY'] ?? '');
}

// ===========================================
// WÄHRUNG & LOKALISIERUNG
// ===========================================

if (!defined('CURRENCY')) {
    define('CURRENCY', 'EUR');
}

if (!defined('CURRENCY_SYMBOL')) {
    define('CURRENCY_SYMBOL', '€');
}

if (!defined('LOCALE')) {
    define('LOCALE', 'de_DE');
}

if (!defined('TIMEZONE')) {
    define('TIMEZONE', 'Europe/Berlin');
}

// ===========================================
// AUTOSYNC-KONFIGURATION
// ===========================================

if (!defined('AUTOSYNC_ENABLED')) {
    define('AUTOSYNC_ENABLED', true);
}

if (!defined('AUTOSYNC_INTERVAL')) {
    define('AUTOSYNC_INTERVAL', 3600); // 1 Stunde
}

// ===========================================
// ZAHLUNGSANBIETER
// ===========================================

if (!defined('STRIPE_PUBLIC_KEY')) {
    define('STRIPE_PUBLIC_KEY', $_ENV['STRIPE_PUBLIC_KEY'] ?? '');
}

if (!defined('STRIPE_SECRET_KEY')) {
    define('STRIPE_SECRET_KEY', $_ENV['STRIPE_SECRET_KEY'] ?? '');
}

if (!defined('STRIPE_WEBHOOK_SECRET')) {
    define('STRIPE_WEBHOOK_SECRET', $_ENV['STRIPE_WEBHOOK_SECRET'] ?? '');
}

// ===========================================
// FEHLERBEHANDLUNG
// ===========================================

if (!defined('ERROR_REPORTING')) {
    switch (ENVIRONMENT) {
        case 'development':
            define('ERROR_REPORTING', E_ALL);
            break;
        case 'testing':
            define('ERROR_REPORTING', E_ALL & ~E_NOTICE);
            break;
        case 'production':
        default:
            define('ERROR_REPORTING', 0);
            break;
    }
}

error_reporting(ERROR_REPORTING);

// ===========================================
// AUTOLOADER & INITIALISIERUNG
// ===========================================

// Composer Autoloader laden
if (file_exists(ROOT_PATH . 'vendor/autoload.php')) {
    require_once ROOT_PATH . 'vendor/autoload.php';
}

// Zeitzone setzen
date_default_timezone_set(TIMEZONE);

// Session starten
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ===========================================
// FEHLERHAFT ZEILE 122 - AUSKOMMENTIERT!
// ===========================================

// Die folgende Zeile war fehlerhaft und wurde auskommentiert:
// require_once ROOT_PATH . 'config/error.php';

// ===========================================
// HILFSFUNKTIONEN
// ===========================================

/**
 * Holt eine Konfigurationsvariable
 */
function config($key, $default = null) {
    return defined($key) ? constant($key) : $default;
}

/**
 * Prüft ob die App in Entwicklung läuft
 */
function is_development() {
    return ENVIRONMENT === 'development';
}

/**
 * Prüft ob die App in Production läuft
 */
function is_production() {
    return ENVIRONMENT === 'production';
}

/**
 * Gibt die vollständige URL zurück
 */
function url($path = '') {
    return BASE_URL . ltrim($path, '/');
}

/**
 * Gibt einen absoluten Pfad zurück
 */
function path($path = '') {
    return ROOT_PATH . ltrim($path, '/');
}

// ===========================================
// INITIALISIERUNGSCHECK
// ===========================================

// Prüfe ob kritische Verzeichnisse existieren
$critical_paths = [
    APP_PATH,
    PUBLIC_PATH,
    UPLOAD_PATH,
    LOG_PATH
];

foreach ($critical_paths as $critical_path) {
    if (!is_dir($critical_path)) {
        mkdir($critical_path, 0755, true);
    }
}

// Debug-Modus aktivieren wenn in Entwicklung
if (is_development()) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
}

// Log-Datei vorbereiten
if (!file_exists(LOG_FILE)) {
    touch(LOG_FILE);
    chmod(LOG_FILE, 0644);
}