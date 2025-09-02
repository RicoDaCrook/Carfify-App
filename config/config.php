<?php
// Carfify v4.0 Enterprise Konfiguration

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Zeitzone
date_default_timezone_set('Europe/Berlin');

// Datenbank-Konfiguration
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'carfify');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');

// Basis-URL
define('BASE_URL', $_ENV['BASE_URL'] ?? 'http://localhost');

// Session-Konfiguration
session_start();

// Autoloader für Klassen
spl_autoload_register(function ($class) {
    $class = str_replace('\\', '/', $class);
    $file = __DIR__ . '/../classes/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Hilfsfunktionen
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function formatCurrency($amount) {
    return number_format($amount, 2, ',', '.') . ' €';
}

function formatDate($date) {
    return date('d.m.Y', strtotime($date));
}
?>