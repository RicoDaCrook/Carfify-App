<?php
/**
 * Carfify Error Debug Tool
 * Zeigt alle PHP-Fehler detailliert an
 * Aufruf: http://deinedomain.de/error_debug.php
 */

// Maximale Fehleranzeige aktivieren
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Zeitzone setzen
date_default_timezone_set('Europe/Berlin');

// Debug-Header ausgeben
echo '<!DOCTYPE html>';
echo '<html><head>';
echo '<title>Carfify Error Debug</title>';
echo '<meta charset="utf-8">';
echo '<style>';
echo 'body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }';
echo '.debug-header { background: #007cba; color: white; padding: 15px; margin-bottom: 20px; border-radius: 5px; }';
echo '.debug-info { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #007cba; }';
echo '.error { background: #fff3cd; border-left-color: #ffc107; }';
echo '.success { background: #d4edda; border-left-color: #28a745; }';
echo '</style>';
echo '</head><body>';

echo '<div class="debug-header">';
echo '<h1>🔧 Carfify Error Debug Tool</h1>';
echo '<p>Zeit: ' . date('Y-m-d H:i:s') . '</p>';
echo '</div>';

// PHP-Version prüfen
$phpVersion = phpversion();
echo '<div class="debug-info ' . (version_compare($phpVersion, '7.4.0', '>=') ? 'success' : 'error') . '">';
echo '<strong>PHP Version:</strong> ' . $phpVersion;
echo '</div>';

// Wichtige PHP-Einstellungen prüfen
$settings = [
    'display_errors' => ini_get('display_errors'),
    'error_reporting' => ini_get('error_reporting'),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size')
];

echo '<div class="debug-info">';
echo '<strong>PHP-Einstellungen:</strong><br>';
foreach ($settings as $key => $value) {
    echo $key . ': ' . $value . '<br>';
}
echo '</div>';

// Prüfen ob index.php existiert
$indexPath = __DIR__ . '/index.php';
echo '<div class="debug-info ' . (file_exists($indexPath) ? 'success' : 'error') . '">';
echo '<strong>Index-Datei:</strong> ' . ($indexPath) . ' - ' . (file_exists($indexPath) ? '✅ Gefunden' : '❌ Nicht gefunden');
echo '</div>';

// Prüfen ob CarfifyCore.php existiert
$corePath = __DIR__ . '/CarfifyCore.php';
echo '<div class="debug-info ' . (file_exists($corePath) ? 'success' : 'error') . '">';
echo '<strong>CarfifyCore:</strong> ' . ($corePath) . ' - ' . (file_exists($corePath) ? '✅ Gefunden' : '❌ Nicht gefunden');
echo '</div>';

// Fehlende Dateien auflisten
$missingFiles = [];
$requiredFiles = [
    'Database.php',
    'Router.php',
    'Auth.php'
];

foreach ($requiredFiles as $file) {
    $filePath = __DIR__ . '/' . $file;
    if (!file_exists($filePath)) {
        $missingFiles[] = $file;
    }
}

if (!empty($missingFiles)) {
    echo '<div class="debug-info error">';
    echo '<strong>Fehlende Dateien:</strong><br>';
    foreach ($missingFiles as $file) {
        echo '❌ ' . $file . '<br>';
    }
    echo '</div>';
} else {
    echo '<div class="debug-info success">';
    echo '<strong>✅ Alle Core-Dateien vorhanden</strong>';
    echo '</div>';
}

echo '<div class="debug-info">';
echo '<strong>🔄 Lade index.php...</strong>';
echo '</div>';

echo '<hr>';
echo '<div style="background: white; padding: 15px; margin: 20px 0; border: 1px solid #ddd; border-radius: 5px;">';
echo '<h3>Index.php Ausgabe:</h3>';
echo '<pre style="background: #f8f9fa; padding: 10px; overflow-x: auto;">';

// Jetzt die index.php laden
if (file_exists($indexPath)) {
    try {
        require_once $indexPath;
    } catch (Throwable $e) {
        echo 'Fehler beim Laden von index.php: ' . $e->getMessage() . "\n";
        echo 'Stack trace: ' . $e->getTraceAsString();
    }
} else {
    echo "Index.php nicht gefunden!";
}

echo '</pre>';
echo '</div>';

echo '</body></html>';
?>