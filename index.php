<?php

// Error Reporting aktivieren
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session starten
session_start();

// Autoloader für Klassen
spl_autoload_register(function ($class) {
    $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    $file = __DIR__ . '/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Basis-Konfiguration laden
require_once 'config/config.php';

// Router initialisieren
require_once 'routing.php';

$router = new Router();
$router->dispatch();
?>