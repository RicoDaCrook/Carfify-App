<?php
/**
 * Carfify Application Entry Point
 * 
 * @package Carfify
 * @version 4.0
 */

// Define base constants
if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__);
}

// Load configuration
require_once BASE_PATH . '/config/config.php';

// Get application instance and run
$app = CarfifyCore::getInstance();
$app->run();