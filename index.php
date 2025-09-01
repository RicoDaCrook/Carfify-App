<?php

// Autoloader laden
require_once __DIR__ . '/vendor/autoload.php';

// Config laden
use Config\Config;

// Application starten
try {
    $config = Config::getInstance();
    
    // Debugging aktivieren wenn in config gesetzt
    if ($config->get('debug', false)) {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    }
    
    // Zeitzone setzen
    date_default_timezone_set($config->get('timezone', 'UTC'));
    
    // Weitere Initialisierung...
    echo "Carfify v4.0 erfolgreich geladen!";
    
} catch (Exception $e) {
    echo 'Fehler: ' . $e->getMessage();
    
    if (Config::getInstance()->get('debug', false)) {
        echo '<pre>' . $e->getTraceAsString() . '</pre>';
    }
}
?>