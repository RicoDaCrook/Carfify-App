<?php
require_once __DIR__ . '/config/Config.php';
require_once __DIR__ . '/classes/Autoloader.php';

use Config\Config;

session_start();

try {
    // Config initialisieren
    $config = Config::getInstance();
    
    // Autoloader registrieren
    Autoloader::register();
    
    // Routing
    $request = $_SERVER['REQUEST_URI'];
    $request = strtok($request, '?');
    
    switch ($request) {
        case '/':
        case '':
            require_once __DIR__ . '/views/home.php';
            break;
            
        case '/login':
            require_once __DIR__ . '/views/login.php';
            break;
            
        case '/register':
            require_once __DIR__ . '/views/register.php';
            break;
            
        case '/dashboard':
            if (!isset($_SESSION['user_id'])) {
                header('Location: /login');
                exit;
            }
            require_once __DIR__ . '/views/dashboard.php';
            break;
            
        case '/logout':
            session_destroy();
            header('Location: /');
            exit;
            
        default:
            if (file_exists(__DIR__ . '/views' . $request . '.php')) {
                require_once __DIR__ . '/views' . $request . '.php';
            } else {
                http_response_code(404);
                require_once __DIR__ . '/views/404.php';
            }
            break;
    }
    
} catch (Exception $e) {
    error_log('Error: ' . $e->getMessage());
    http_response_code(500);
    require_once __DIR__ . '/views/500.php';
}