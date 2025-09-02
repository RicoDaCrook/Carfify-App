<?php

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Zeitzone setzen
date_default_timezone_set('Europe/Berlin');

// Environment laden
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        list($key, $value) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
        putenv(trim($key) . '=' . trim($value));
    }
}

// Autoloader registrieren
spl_autoload_register(function ($class) {
    $prefix = '';
    $base_dir = __DIR__ . '/../';
    $len = strlen($prefix);
    
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Session starten
if (session_status() === PHP_SESSION_NONE) {
    $sessionConfig = Config\AppConfig::getInstance()->get('session');
    
    ini_set('session.name', $sessionConfig['name']);
    ini_set('session.cookie_lifetime', $sessionConfig['lifetime']);
    ini_set('session.cookie_path', $sessionConfig['path']);
    ini_set('session.cookie_domain', $sessionConfig['domain']);
    ini_set('session.cookie_secure', $sessionConfig['secure']);
    ini_set('session.cookie_httponly', $sessionConfig['http_only']);
    
    session_start();
}

// Error Handler registrieren
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
});

// Exception Handler
set_exception_handler(function ($exception) {
    error_log($exception->getMessage() . ' in ' . $exception->getFile() . ':' . $exception->getLine());
    
    if (Config\AppConfig::getInstance()->get('app.debug')) {
        echo '<h1>Fehler aufgetreten</h1>';
        echo '<p><strong>Nachricht:</strong> ' . htmlspecialchars($exception->getMessage()) . '</p>';
        echo '<p><strong>Datei:</strong> ' . htmlspecialchars($exception->getFile()) . ':' . $exception->getLine() . '</p>';
        echo '<pre>' . htmlspecialchars($exception->getTraceAsString()) . '</pre>';
    } else {
        echo 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.';
    }
});