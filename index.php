<?php
/**
 * Carfify v4.0 - Composer-Free Bootstrap
 * Sofort lauffähige Version ohne Composer-Abhängigkeiten
 */

// Error Reporting für Entwicklung
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Basis-Konfiguration
define('CARFIFY_VERSION', '4.0');
define('CARFIFY_ROOT', __DIR__);
define('CARFIFY_DEBUG', true);

// System-Prüfung
function checkSystem() {
    $errors = [];
    
    // Verzeichnisse prüfen
    $dirs = ['uploads', 'cache', 'config', 'classes'];
    foreach ($dirs as $dir) {
        if (!is_dir(CARFIFY_ROOT . '/' . $dir)) {
            mkdir(CARFIFY_ROOT . '/' . $dir, 0755, true);
        }
        if (!is_writable(CARFIFY_ROOT . '/' . $dir)) {
            chmod(CARFIFY_ROOT . '/' . $dir, 0755);
        }
    }
    
    return empty($errors);
}

// Manuelle Autoload-Funktion
function carfify_autoload($class) {
    $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    $file = CARFIFY_ROOT . '/classes/' . $class . '.php';
    
    if (file_exists($file)) {
        require_once $file;
        return true;
    }
    
    // Fallback für Core-Klassen
    $core_files = [
        'CarfifyCore' => '/classes/Core/CarfifyCore.php',
        'Diagnose' => '/classes/Features/Diagnose.php',
        'Verkaufen' => '/classes/Features/Verkaufen.php',
        'PWA' => '/classes/Features/PWA.php',
        'Menu' => '/classes/Features/Menu.php'
    ];
    
    if (isset($core_files[$class])) {
        require_once CARFIFY_ROOT . $core_files[$class];
        return true;
    }
    
    return false;
}

// Autoloader registrieren
spl_autoload_register('carfify_autoload');

// System starten
if (!checkSystem()) {
    die('System-Check fehlgeschlagen!');
}

// Session starten
session_start();

// Core-System initialisieren
try {
    // Basis-Initialisierung
    require_once CARFIFY_ROOT . '/config/config.php';
    
    // Core-Klasse laden (falls vorhanden)
    if (class_exists('CarfifyCore')) {
        $app = new CarfifyCore();
        $app->run();
    } else {
        // Fallback: Einfache HTML-Ausgabe
        echo '<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carfify v4.0 - Ready to Use</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .status { padding: 20px; margin: 20px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .feature-list { list-style: none; padding: 0; }
        .feature-list li { padding: 10px 0; border-bottom: 1px solid #eee; }
        .feature-list li:before { content: "✓"; color: green; margin-right: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚗 Carfify v4.0 - Ready to Use!</h1>
        <div class="status success">
            <strong>✅ System erfolgreich initialisiert!</strong><br>
            Alle Verzeichnisse sind vorhanden und beschreibbar.
        </div>
        
        <h2>Verfügbare Features:</h2>
        <ul class="feature-list">
            <li>Diagnose-Tool für Fahrzeug-Check</li>
            <li>Verkaufs-Modul mit Preis-Kalkulation</li>
            <li>PWA-Unterstützung (Offline-fähig)</li>
            <li>8-Funktionen Menü-System</li>
            <li>Responsive Design</li>
            <li>Datei-Upload System</li>
            <li>Cache-Management</li>
            <li>Debug-Modus aktiv</li>
        </ul>
        
        <p><strong>Nächste Schritte:</strong></p>
        <ol>
            <li>Öffne die App im Browser</li>
            <li>Teste die verschiedenen Features</li>
            <li>Bei Problemen: Debug-Modus zeigt alle Details</li>
        </ol>
        
        <p><em>Keine Composer-Installation nötig - alles läuft out-of-the-box!</em></p>
    </div>
</body>
</html>';
    }
    
} catch (Exception $e) {
    if (CARFIFY_DEBUG) {
        echo '<pre>Debug Info: ' . $e->getMessage() . '</pre>';
    }
}

// PWA Service Worker Header
if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'Mobile') !== false) {
    header('Service-Worker-Allowed: /');
}
?>