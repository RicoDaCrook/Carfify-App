<?php
// Carfify System Health Check
header('Content-Type: application/json');

$checks = [];

// PHP Version
$checks['php_version'] = [
    'required' => '7.4+',
    'current' => PHP_VERSION,
    'status' => version_compare(PHP_VERSION, '7.4.0', '>=') ? 'ok' : 'error'
];

// Database Connection
try {
    $db = new PDO('sqlite:carfify.db');
    $checks['database'] = ['status' => 'ok', 'message' => 'Connected'];
} catch (Exception $e) {
    $checks['database'] = ['status' => 'error', 'message' => $e->getMessage()];
}

// Required Files
$required_files = ['manifest.json', 'sw.js', 'index.php'];
foreach ($required_files as $file) {
    $checks['files'][$file] = file_exists($file) ? 'ok' : 'missing';
}

// PWA Check
$checks['pwa'] = [
    'manifest' => file_exists('manifest.json') && json_decode(file_get_contents('manifest.json')) ? 'ok' : 'error',
    'service_worker' => file_exists('sw.js') ? 'ok' : 'error'
];

echo json_encode($checks, JSON_PRETTY_PRINT);
?>