<?php
/**
 * Carfify v4.0 - Main Entry Point
 * Verbesserte Error-Handling für Setup-Probleme
 */

// Error Reporting für Setup-Phase
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Autoloader mit Fallback
$autoloadPaths = [
    __DIR__ . '/vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php'
];

$autoloadFound = false;
foreach ($autoloadPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $autoloadFound = true;
        break;
    }
}

if (!$autoloadFound) {
    die("<h1>🚫 Setup Fehler</h1><p>Composer Autoloader nicht gefunden. Führe aus:</p><pre>composer install</pre><p>oder öffne <a href='setup_fix.php'>setup_fix.php</a>");
}

// System-Prüfungen
$errors = [];

if (!is_writable('uploads')) {
    $errors[] = "Ordner 'uploads' ist nicht beschreibbar";
}

if (!is_writable('cache')) {
    $errors[] = "Ordner 'cache' ist nicht beschreibbar";
}

if ($errors) {
    die("<h1>🚫 Setup Fehler</h1><ul><li>" . implode('</li><li>', $errors) . "</li></ul><p>Öffne <a href='setup_fix.php'>setup_fix.php</a> für automatische Behebung</p>");
}

// Alles OK - App starten
require_once 'app/bootstrap.php';

// PWA Service Worker Header
header('Service-Worker-Allowed: /');

// Start Application
$app = new \Carfify\Core\Application();
$app->run();
?>