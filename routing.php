<?php

class Router {
    private $routes = [];
    
    public function __construct() {
        $this->setupRoutes();
    }
    
    private function setupRoutes() {
        // Haupt-Navigation
        $this->routes['GET']['/'] = 'HomeController@landingPage';
        $this->routes['GET']['/home'] = 'HomeController@landingPage';
        
        // Fahrzeug-Auswahl
        $this->routes['GET']['/fahrzeug-auswahl'] = 'VehicleController@selection';
        $this->routes['POST']['/fahrzeug-auswahl'] = 'VehicleController@processSelection';
        $this->routes['GET']['/fahrzeug-suche'] = 'VehicleController@search';
        
        // Problem-Beschreibung
        $this->routes['GET']['/problem-beschreibung'] = 'DiagnosisController@problemForm';
        $this->routes['POST']['/problem-beschreibung'] = 'DiagnosisController@processProblem';
        
        // KI-Diagnose
        $this->routes['GET']['/ki-analyse'] = 'AiDiagnosisController@analyze';
        $this->routes['POST']['/ki-analyse/interaktiv'] = 'AiDiagnosisController@interactive';
        $this->routes['GET']['/ki-analyse/fortschritt'] = 'AiDiagnosisController@progress';
        
        // Preiskalkulation
        $this->routes['GET']['/preis-kalkulation'] = 'PricingController@calculate';
        $this->routes['POST']['/preis-kalkulation/anfrage'] = 'PricingController@process';
        
        // Werkstattsuche
        $this->routes['GET']['/werkstatt-suche'] = 'WorkshopController@search';
        $this->routes['POST']['/werkstatt-suche/filter'] = 'WorkshopController@filter';
        
        // API-Endpunkte
        $this->routes['GET']['/api/vehicles'] = 'ApiController@getVehicles';
        $this->routes['GET']['/api/workshops'] = 'ApiController@getWorkshops';
        $this->routes['POST']['/api/diagnose'] = 'ApiController@diagnose';
    }
    
    public function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Entferne Base-Path
        $basePath = '/var/www';
        $path = str_replace($basePath, '', $path);
        
        if (isset($this->routes[$method][$path])) {
            $route = $this->routes[$method][$path];
            list($controller, $action) = explode('@', $route);
            
            $controllerFile = "controllers/{$controller}.php";
            if (file_exists($controllerFile)) {
                require_once $controllerFile;
                $controllerInstance = new $controller();
                $controllerInstance->$action();
            } else {
                $this->error404();
            }
        } else {
            $this->error404();
        }
    }
    
    private function error404() {
        http_response_code(404);
        echo "Seite nicht gefunden";
    }
}