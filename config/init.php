<?php
/**
 * Carfify Initialisierungsskript
 * Lädt alle notwendigen Konfigurationen und startet die Session
 */

// Error-Reporting aktivieren (nur in Entwicklung)
if (getenv('ENVIRONMENT') === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Zeitzone setzen
date_default_timezone_set('Europe/Berlin');

// Sicherheits-Header setzen
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Content-Type für JSON-APIs
if (strpos($_SERVER['REQUEST_URI'], '/api/') === 0) {
    header('Content-Type: application/json; charset=utf-8');
}

// Session starten
session_start();

// Session-Sicherheit erhöhen
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

// API Rate Limiting
class RateLimiter {
    private static $maxRequests = 100;
    private static $timeWindow = 3600; // 1 Stunde
    
    public static function checkLimit($identifier) {
        $key = 'rate_limit_' . md5($identifier);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'count' => 1,
                'reset_time' => time() + self::$timeWindow
            ];
            return true;
        }
        
        if (time() > $_SESSION[$key]['reset_time']) {
            $_SESSION[$key] = [
                'count' => 1,
                'reset_time' => time() + self::$timeWindow
            ];
            return true;
        }
        
        if ($_SESSION[$key]['count'] >= self::$maxRequests) {
            http_response_code(429);
            echo json_encode(['error' => 'Rate limit exceeded']);
            exit;
        }
        
        $_SESSION[$key]['count']++;
        return true;
    }
}

// IP-basiertes Rate Limiting für API-Aufrufe
if (strpos($_SERVER['REQUEST_URI'], '/api/') === 0) {
    RateLimiter::checkLimit($_SERVER['REMOTE_ADDR']);
}

// API-Schlüssel aus Umgebungsvariablen laden
if (!defined('CLAUDE_API_KEY')) {
    define('CLAUDE_API_KEY', getenv('CLAUDE_API_KEY') ?: '');
}

if (!defined('GOOGLE_MAPS_API_KEY')) {
    define('GOOGLE_MAPS_API_KEY', getenv('GOOGLE_MAPS_API_KEY') ?: '');
}

if (!defined('GEMINI_API_KEY')) {
    define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: '');
}

if (!defined('IMAGE_ANALYSIS_API_KEY')) {
    define('IMAGE_ANALYSIS_API_KEY', getenv('IMAGE_ANALYSIS_API_KEY') ?: '');
}

if (!defined('MARKET_ANALYSIS_API_KEY')) {
    define('MARKET_ANALYSIS_API_KEY', getenv('MARKET_ANALYSIS_API_KEY') ?: '');
}

// Datenbank-Konfiguration laden
require_once __DIR__ . '/database.php';

// Autoloader für Klassen
spl_autoload_register(function ($className) {
    $baseDir = __DIR__ . '/../classes/';
    $file = $baseDir . str_replace('\\', '/', $className) . '.php';
    
    if (file_exists($file)) {
        require_once $file;
    }
});

// CORS-Header für API-Endpunkte
if (strpos($_SERVER['REQUEST_URI'], '/api/') === 0) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    
    // Preflight-Requests beantworten
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

// Basis-URL definieren
if (!defined('BASE_URL')) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    define('BASE_URL', $protocol . '://' . $host . '/');
}

// Upload-Verzeichnis definieren
if (!defined('UPLOAD_DIR')) {
    define('UPLOAD_DIR', __DIR__ . '/../uploads/');
}

// Log-Verzeichnis definieren
if (!defined('LOG_DIR')) {
    define('LOG_DIR', __DIR__ . '/../logs/');
}

// Error-Handler registrieren
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    $logMessage = sprintf(
        '[%s] %s: %s in %s:%d\n',
        date('Y-m-d H:i:s'),
        $errno,
        $errstr,
        $errfile,
        $errline
    );
    
    error_log($logMessage, 3, LOG_DIR . 'errors.log');
    
    if (getenv('ENVIRONMENT') === 'development') {
        return false; // Standard-Error-Handler verwenden
    }
    
    return true;
});

// Exception-Handler registrieren
set_exception_handler(function ($exception) {
    $logMessage = sprintf(
        '[%s] Exception: %s in %s:%d\nStack trace:\n%s\n',
        date('Y-m-d H:i:s'),
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        $exception->getTraceAsString()
    );
    
    error_log($logMessage, 3, LOG_DIR . 'exceptions.log');
    
    if (getenv('ENVIRONMENT') === 'development') {
        throw $exception;
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Internal server error']);
    }
});