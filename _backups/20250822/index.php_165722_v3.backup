<?php
/**
 * Carfify v4.0 - Main Entry Point
 * 
 * @package Carfify
 * @version 4.0
 * @author Carfify Team
 * @license MIT
 */

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define base constants
if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__);
}

if (!defined('CORE_PATH')) {
    define('CORE_PATH', BASE_PATH . '/core');
}

// Include core files with correct paths
require_once CORE_PATH . '/CarfifyCore.php';
require_once CORE_PATH . '/Database.php';
require_once CORE_PATH . '/SessionManager.php';
require_once CORE_PATH . '/Router.php';
require_once CORE_PATH . '/Config.php';

// Initialize the application
try {
    // Load configuration
    Config::load();
    
    // Initialize database connection
    Database::getInstance();
    
    // Start session management
    SessionManager::start();
    
    // Initialize core system
    $app = new CarfifyCore();
    
    // Initialize router
    $router = new Router();
    
    // Load routes
    require_once BASE_PATH . '/routes/web.php';
    
    // Dispatch the request
    $router->dispatch();
    
} catch (Exception $e) {
    // Handle initialization errors
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        echo '<pre>';
        echo "Carfify Initialization Error:\n";
        echo "Message: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . "\n";
        echo "Line: " . $e->getLine() . "\n";
        echo "Stack Trace:\n" . $e->getTraceAsString();
        echo '</pre>';
    } else {
        // Log error and show user-friendly message
        error_log('Carfify Error: ' . $e->getMessage());
        echo '<h1>System Error</h1><p>Please try again later.</p>';
    }
}

// Shutdown function for cleanup
register_shutdown_function(function() {
    // Close database connection if exists
    if (class_exists('Database')) {
        Database::close();
    }
    
    // Save session data
    if (class_exists('SessionManager')) {
        SessionManager::close();
    }
});
