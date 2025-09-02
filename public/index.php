<?php

// Load Bootstrap
require_once __DIR__ . '/../bootstrap.php';

// Get Config
$config = Config\Config::getInstance();

// Debug Mode
if ($config->get('app.debug')) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// Route Request
$request_uri = $_SERVER['REQUEST_URI'];
$request_method = $_SERVER['REQUEST_METHOD'];

// Simple Router
if ($request_uri === '/' || $request_uri === '/index.php') {
    include __DIR__ . '/../views/home.php';
} elseif ($request_uri === '/api/config') {
    header('Content-Type: application/json');
    echo json_encode([
        'app_name' => $config->get('app.name'),
        'version' => $config->get('app.version'),
        'debug' => $config->get('app.debug')
    ]);
} else {
    http_response_code(404);
    echo '404 - Page Not Found';
}