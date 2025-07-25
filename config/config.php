<?php
// Carfify Hauptkonfiguration
define('SITE_NAME', 'Carfify');
define('SITE_URL', 'https://deine-domain.de');
define('DEBUG_MODE', false);

// Zeitzone
date_default_timezone_set('Europe/Berlin');

// Error Reporting
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Autoloader für Klassen
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/classes/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
?>