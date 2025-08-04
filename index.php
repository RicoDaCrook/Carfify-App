<?php
// Carfify v4.0 - Main Entry Point
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session starten
session_start();

// Autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Konfiguration laden
try {
    require_once __DIR__ . '/config/config.php';
} catch (Exception $e) {
    die('Konfigurationsfehler: ' . $e->getMessage());
}

// Basis-Initialisierung
require_once __DIR__ . '/core/bootstrap.php';

// Routing
$request = $_SERVER['REQUEST_URI'];
$router = new Core\Router();

// Definierte Routen
$router->get('/', 'HomeController@index');
$router->get('/diagnose', 'DiagnoseController@index');
$router->get('/verkaufen', 'SellController@index');
$router->get('/api/health', 'ApiController@health');

// Route ausführen
try {
    $router->dispatch($request);
} catch (Exception $e) {
    http_response_code(500);
    echo '<h1>Server Fehler</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>';
    error_log($e->getMessage());
}