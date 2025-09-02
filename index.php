<?php
/**
 * Carfify v4.0 - Main Entry Point
 * 
 * @version 4.0
 * @author Carfify AI
 */

// Error reporting für Development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define application root
if (!defined('APP_ROOT')) {
    define('APP_ROOT', __DIR__);
}

// Define core directory
if (!defined('CORE_PATH')) {
    define('CORE_PATH', APP_ROOT . '/core');
}

// Autoloader registrieren
require_once APP_ROOT . '/vendor/autoload.php';

// Fallback Autoloader falls Composer nicht vorhanden
if (!class_exists('Composer\Autoload\ClassLoader')) {
    spl_autoload_register(function($className) {
        $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
        $file = APP_ROOT . '/' . $className . '.php';
        
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
        
        // Alternative Pfade
        $alternatives = [
            CORE_PATH . '/' . $className . '.php',
            CORE_PATH . '/' . str_replace('Core/', '', $className) . '.php',
            APP_ROOT . '/src/' . $className . '.php'
        ];
        
        foreach ($alternatives as $altFile) {
            if (file_exists($altFile)) {
                require_once $altFile;
                return true;
            }
        }
        
        return false;
    });
}

try {
    // Config laden
    if (!class_exists('Config')) {
        if (file_exists(CORE_PATH . '/Config.php')) {
            require_once CORE_PATH . '/Config.php';
        } else {
            throw new Exception('Config.php nicht gefunden in: ' . CORE_PATH);
        }

 // Load configuration
require_once __DIR__ . '/config/Config.php';

// Get config instance
$config = Config::getInstance();

// Set error reporting based on debug mode
error_reporting($config->get('app.debug') ? E_ALL : 0);
ini_set('display_errors', $config->get('app.debug') ? 1 : 0);

 // Initialize application
 $app = new App($config);