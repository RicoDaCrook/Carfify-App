<?php
/**
 * Carfify Configuration File
 * 
 * @package Carfify
 * @version 4.0
 */

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session Configuration - VOR session_start() setzen!
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', 3600);
ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 100);

// Session starten NACH allen ini_set Befehlen
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Zeitzone
ini_set('date.timezone', 'Europe/Berlin');

// Memory Limit
ini_set('memory_limit', '256M');

// Upload Limits
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '10M');

// Base Path Definition
if (!defined('BASE_PATH')) {
    define('BASE_PATH', realpath(dirname(__FILE__) . '/../'));
}

// URL Base
if (!defined('BASE_URL')) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('BASE_URL', $protocol . '://' . $host);
}

// Environment
if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', $_SERVER['HTTP_HOST'] === 'localhost' ? 'development' : 'production');
}

// Database Configuration
if (!defined('DB_CONFIG')) {
    define('DB_CONFIG', [
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'database' => $_ENV['DB_NAME'] ?? 'carfify',
        'username' => $_ENV['DB_USER'] ?? 'root',
        'password' => $_ENV['DB_PASS'] ?? '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    ]);
}

// Security Configuration
if (!defined('SECURITY_CONFIG')) {
    define('SECURITY_CONFIG', [
        'csrf_token_expiry' => 3600,
        'max_login_attempts' => 5,
        'lockout_duration' => 900, // 15 Minuten
        'password_min_length' => 8,
        'session_timeout' => 3600,
    ]);
}

// File Upload Configuration
if (!defined('UPLOAD_CONFIG')) {
    define('UPLOAD_CONFIG', [
        'max_file_size' => 10 * 1024 * 1024, // 10MB
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'],
        'upload_path' => BASE_PATH . '/uploads/',
        'url_path' => BASE_URL . '/uploads/',
    ]);
}

// Cache Configuration
if (!defined('CACHE_CONFIG')) {
    define('CACHE_CONFIG', [
        'enabled' => ENVIRONMENT === 'production',
        'ttl' => 3600,
        'path' => BASE_PATH . '/cache/',
    ]);
}

// API Configuration
if (!defined('API_CONFIG')) {
    define('API_CONFIG', [
        'rate_limit' => 100, // requests per minute
        'timeout' => 30,
        'retry_attempts' => 3,
    ]);
}

// Logging Configuration
if (!defined('LOG_CONFIG')) {
    define('LOG_CONFIG', [
        'enabled' => true,
        'level' => ENVIRONMENT === 'development' ? 'debug' : 'error',
        'path' => BASE_PATH . '/logs/',
        'max_files' => 10,
        'max_size' => 10 * 1024 * 1024, // 10MB
    ]);
}

// Auto-Loader Setup
require_once BASE_PATH . '/vendor/autoload.php';

// Core Classes laden
require_once BASE_PATH . '/core/CarfifyCore.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Router.php';
require_once BASE_PATH . '/core/View.php';
require_once BASE_PATH . '/core/Controller.php';
require_once BASE_PATH . '/core/Model.php';
require_once BASE_PATH . '/core/Auth.php';
require_once BASE_PATH . '/core/Session.php';
require_once BASE_PATH . '/core/Validation.php';
require_once BASE_PATH . '/core/Logger.php';
require_once BASE_PATH . '/core/Cache.php';
require_once BASE_PATH . '/core/Security.php';
require_once BASE_PATH . '/core/Email.php';
require_once BASE_PATH . '/core/FileUpload.php';
require_once BASE_PATH . '/core/ApiClient.php';
require_once BASE_PATH . '/core/Helpers.php';

// Initialize Application
$app = CarfifyCore::getInstance();