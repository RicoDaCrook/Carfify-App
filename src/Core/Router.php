<?php
namespace Core;

class Router
{
    public function dispatch(): void
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = trim($uri, '/');

        switch ($uri) {
            case '':
            case 'home':
                $this->loadController('HomeController', 'index');
                break;
            default:
                http_response_code(404);
                echo '404 - Seite nicht gefunden';
        }
    }

    private function loadController(string $controller, string $method): void
    {
        $class = "Controllers\\{$controller}";
        if (class_exists($class)) {
            $instance = new $class();
            if (method_exists($instance, $method)) {
                $instance->$method();
            }
        }
    }
}