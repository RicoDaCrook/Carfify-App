<?php
namespace Carfify;

class Router
{
    private $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'DELETE' => []
    ];
    
    public function __construct()
    {
        $this->registerRoutes();
    }
    
    private function registerRoutes()
    {
        // API Routes
        $this->routes['POST']['/api/diagnose'] = 'Api\DiagnoseController@diagnose';
        $this->routes['POST']['/api/estimate'] = 'Api\PriceController@estimate';
        $this->routes['POST']['/api/sell'] = 'Api\SellController@createListing';
        
        // Frontend Routes
        $this->routes['GET']['/'] = 'HomeController@index';
        $this->routes['GET']['/diagnose'] = 'DiagnoseController@show';
        $this->routes['GET']['/sell'] = 'SellController@show';
    }
    
    public function handleRequest()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        if (isset($this->routes[$method][$path])) {
            $handler = $this->routes[$method][$path];
            $this->callHandler($handler);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Route not found']);
        }
    }
    
    private function callHandler(string $handler)
    {
        [$controller, $method] = explode('@', $handler);
        $controller = "Carfify\\Controllers\\$controller";
        
        if (class_exists($controller)) {
            $instance = new $controller();
            if (method_exists($instance, $method)) {
                $instance->$method();
                return;
            }
        }
        
        http_response_code(500);
        echo json_encode(['error' => 'Handler not found']);
    }
}