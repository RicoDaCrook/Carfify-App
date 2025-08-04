<?php
// Carfify API Entry Point
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

// CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Health Check
if ($_SERVER['REQUEST_URI'] === '/api/health') {
    echo json_encode([
        'status' => 'ok',
        'timestamp' => date('c'),
        'version' => '4.0',
        'php' => PHP_VERSION,
        'memory' => memory_get_usage(true),
        'uptime' => time() - $_SERVER['REQUEST_TIME']
    ]);
    exit;
}

// Standard Response
http_response_code(404);
echo json_encode(['error' => 'Endpoint not found']);
?>