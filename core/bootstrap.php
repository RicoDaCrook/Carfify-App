<?php
// Carfify Bootstrap - System Initialisierung

// Basis-Checks
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    die('PHP 7.4 oder höher erforderlich. Aktuelle Version: ' . PHP_VERSION);
}

// Notwendige Extensions prüfen
$required = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'openssl'];
foreach ($required as $ext) {
    if (!extension_loaded($ext)) {
        die('Fehlende PHP Extension: ' . $ext);
    }
}

// Konstanten definieren
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . '/..');
}

// Autoloader für Core-Klassen
spl_autoload_register(function ($class) {
    $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    $file = ROOT_PATH . '/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Datenbankverbindung herstellen
try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die('Datenbankverbindung fehlgeschlagen: ' . $e->getMessage());
}

// Globale Funktionen
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function asset($path) {
    return '/assets/' . ltrim($path, '/');
}
?>