<?php

namespace Core;

class Router
{
    private static $routes = [];
    private static $currentRoute = null;

    public static function get($uri, $callback)
    {
        self::addRoute('GET', $uri, $callback);
    }

    public static function post($uri, $callback)
    {
        self::addRoute('POST', $uri, $callback);
    }

    public static function put($uri, $callback)
    {
        self::addRoute('PUT', $uri, $callback);
    }

    public static function delete($uri, $callback)
    {
        self::addRoute('DELETE', $uri, $callback);
    }

    private static function addRoute($method, $uri, $callback)
    {
        self::$routes[] = [
            'method' => $method,
            'uri' => $uri,
            'callback' => $callback
        ];
    }

    public static function dispatch()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];

        foreach (self::$routes as $route) {
            if ($route['method'] === $method && self::match($route['uri'], $uri, $matches)) {
                self::$currentRoute = $route;
                
                if (is_callable($route['callback'])) {
                    return call_user_func_array($route['callback'], $matches);
                }
                
                if (is_string($route['callback'])) {
                    list($controller, $method) = explode('@', $route['callback']);
                    $controller = "App\\Controllers\\{$controller}";
                    $controllerInstance = new $controller();
                    return call_user_func_array([$controllerInstance, $method], $matches);
                }
            }
        }

        http_response_code(404);
        echo "404 Not Found";
    }

    private static function match($route, $uri, &$matches = [])
    {
        $route = preg_replace('/\{([^}]+)\}/', '([^/]+)', $route);
        $route = '#^' . $route . '$#';
        
        if (preg_match($route, $uri, $matches)) {
            array_shift($matches);
            return true;
        }
        
        return false;
    }
}