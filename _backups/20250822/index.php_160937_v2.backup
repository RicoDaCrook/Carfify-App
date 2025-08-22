<?php
/**
 * Carfify v4.0 - Index Datei
 * 
 * Dies ist der Haupteinstiegspunkt der Anwendung
 */

// PHP-Fehler nur im Debug-Modus anzeigen
if (isset($_GET['debug']) || file_exists(__DIR__ . '/.debug')) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', 0);
}

// Basis-Konstanten definieren
define('CARFIFY_ROOT', __DIR__);
define('CARFIFY_CORE', CARFIFY_ROOT . '/CarfifyCore.php');
define('CARFIFY_CONFIG', CARFIFY_ROOT . '/config.php');

// Session starten
session_start();

try {
    // Prüfen ob CarfifyCore existiert
    if (!file_exists(CARFIFY_CORE)) {
        throw new Exception('CarfifyCore.php nicht gefunden in: ' . CARFIFY_CORE);
    }
    
    // CarfifyCore laden
    require_once CARFIFY_CORE;
    
    // Prüfen ob alle benötigten Dateien vorhanden sind
    $requiredFiles = [
        'Database.php',
        'Router.php', 
        'Auth.php'
    ];
    
    foreach ($requiredFiles as $file) {
        $filePath = CARFIFY_ROOT . '/' . $file;
        if (!file_exists($filePath)) {
            throw new Exception('Fehlende Datei: ' . $file . ' (Pfad: ' . $filePath . ')');
        }
    }
    
    // CarfifyCore initialisieren
    $app = new CarfifyCore();
    
    // Anwendung starten
    $app->run();
    
} catch (Throwable $e) {
    // Fehlerbehandlung
    if (isset($_GET['debug']) || file_exists(__DIR__ . '/.debug')) {
        // Im Debug-Modus: Detaillierte Fehleranzeige
        echo '<!DOCTYPE html>';
        echo '<html><head><title>Carfify - Fehler</title></head><body>';
        echo '<h1>Carfify Fehler</h1>';
        echo '<h3>' . htmlspecialchars($e->getMessage()) . '</h3>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        echo '<hr>';
        echo '<p>Tip: Nutze <a href="error_debug.php">error_debug.php</a> für weitere Diagnose.</p>';
        echo '</body></html>';
    } else {
        // Produktionsmodus: Einfache Fehlerseite
        http_response_code(500);
        echo '<!DOCTYPE html>';
        echo '<html><head><title>500 - Serverfehler</title></head><body>';
        echo '<h1>500 - Interner Serverfehler</h1>';
        echo '<p>Es ist ein Fehler aufgetreten. Bitte versuchen Sie es später erneut.</p>';
        echo '</body></html>';
    }
    
    // Fehler loggen
    error_log('Carfify Fehler: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
}
?>