<?php
/**
 * Carfify v4.0 - Main Entry Point
 * Enterprise Grade Car Management System
 */

declare(strict_types=1);

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Define application root
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__FILE__) . DIRECTORY_SEPARATOR);
}

// Define core directory
if (!defined('CORE_PATH')) {
    define('CORE_PATH', APP_ROOT . 'core' . DIRECTORY_SEPARATOR);
}

// Load configuration first
if (!file_exists(CORE_PATH . 'Config.php')) {
    die('Critical Error: Config.php not found in ' . CORE_PATH);
}

// Load configuration class
require_once CORE_PATH . 'Config.php';

// Initialize configuration
try {
    Config::load();
} catch (Exception $e) {
    die('Configuration Error: ' . $e->getMessage());
}

// Set timezone from config
date_default_timezone_set(Config::get('app.timezone', 'UTC'));

// Start session
session_start();

// Load autoloader for other classes
if (file_exists(APP_ROOT . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php')) {
    require_once APP_ROOT . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
} else {
    // Fallback autoloader
    spl_autoload_register(function ($class) {
        $file = APP_ROOT . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    });
}

// Check if application is installed
if (!file_exists(APP_ROOT . 'config' . DIRECTORY_SEPARATOR . 'app.php')) {
    header('Location: install.php');
    exit;
}

// Initialize application
try {
    $app = new Core\Application();
    $app->run();
} catch (Exception $e) {
    if (Config::get('app.debug', false)) {
        throw $e;
    } else {
        error_log($e->getMessage());
        header('HTTP/1.1 500 Internal Server Error');
        echo 'An error occurred. Please try again later.';
    }
}