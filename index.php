<?php
// index.php - Haupt-Einstiegspunkt

// Error Reporting aktivieren
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Autoloader einbinden
require_once __DIR__ . '/vendor/autoload.php';

// Session nur starten wenn nicht bereits aktiv
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Config laden
use Config\AppConfig;

// App starten
try {
    $config = AppConfig::getInstance();
    $app = new Core\App($config);
    $app->run();
} catch (Exception $e) {
    echo '<h1>Fehler aufgetreten</h1>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    if ($config->get('debug', false)) {
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    }
}