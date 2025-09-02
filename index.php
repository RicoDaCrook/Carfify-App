<?php

// Bootstrap laden
require_once __DIR__ . '/Config/bootstrap.php';

// Config laden
use Config\AppConfig;

// Konfiguration abrufen
$config = AppConfig::getInstance();

// Debugging aktivieren wenn in Config gesetzt
if ($config->get('app.debug')) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Basis-URL für Redirects
$baseUrl = $config->get('app.url');

// Routing oder Controller-Initialisierung
// Hier können Sie Ihre Anwendungslogik einbauen

echo '<!DOCTYPE html>';
echo '<html lang="de">';
echo '<head>';
echo '    <meta charset="UTF-8">';
echo '    <meta name="viewport" content="width=device-width, initial-scale=1.0">';
echo '    <title>' . htmlspecialchars($config->get('app.name')) . '</title>';
echo '</head>';
echo '<body>';
echo '    <h1>Willkommen bei ' . htmlspecialchars($config->get('app.name')) . '</h1>';
echo '    <p>Environment: ' . htmlspecialchars($config->get('app.env')) . '</p>';
echo '    <p>Debug Mode: ' . ($config->get('app.debug') ? 'Aktiviert' : 'Deaktiviert') . '</p>';
echo '</body>';
echo '</html>';