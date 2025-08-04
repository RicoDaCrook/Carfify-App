<?php
// Carfify Debug Tool v2.0
error_reporting(E_ALL);
ini_set('display_errors', 1);

function checkSystemHealth() {
    $issues = [];
    
    // PHP Version prüfen
    if (version_compare(PHP_VERSION, '7.4.0', '<')) {
        $issues[] = 'PHP Version zu alt: ' . PHP_VERSION;
    }
    
    // Wichtige Extensions prüfen
    $required = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'openssl'];
    foreach ($required as $ext) {
        if (!extension_loaded($ext)) {
            $issues[] = 'Fehlende Extension: ' . $ext;
        }
    }
    
    // Dateirechte prüfen
    $paths = ['config/', 'uploads/', 'cache/'];
    foreach ($paths as $path) {
        if (!is_writable($path)) {
            $issues[] = 'Nicht beschreibbar: ' . $path;
        }
    }
    
    return $issues;
}

$issues = checkSystemHealth();
if (!empty($issues)) {
    echo '<h2>System Issues gefunden:</h2><ul>';
    foreach ($issues as $issue) {
        echo '<li>' . htmlspecialchars($issue) . '</li>';
    }
    echo '</ul>';
} else {
    echo '<h2>✓ System Health OK</h2>';
}
?>