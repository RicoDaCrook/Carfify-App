<?php

class Router
{
    private $routes = [];
    private $basePath = '';
    private $currentGroup = '';

    public function __construct($basePath = '')
    {
        $this->basePath = rtrim($basePath, '/');
    }

    public function get($path, $handler)
    {
        return $this->addRoute('GET', $path, $handler);
    }

    public function post($path, $handler)
    {
        return $this->addRoute('POST', $path, $handler);
    }

    public function put($path, $handler)
    {
        return $this->addRoute('PUT', $path, $handler);
    }

    public function delete($path, $handler)
    {
        return $this->addRoute('DELETE', $path, $handler);
    }

    public function group($prefix, $callback)
    {
        $previousGroup = $this->currentGroup;
        $this->currentGroup = $previousGroup . '/' . trim($prefix, '/');
        
        $callback($this);
        
        $this->currentGroup = $previousGroup;
    }

    private function addRoute($method, $path, $handler)
    {
        $fullPath = $this->basePath . $this->currentGroup . '/' . trim($path, '/');
        $fullPath = rtrim($fullPath, '/');
        $fullPath = $fullPath ?: '/';

        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $fullPath,
            'handler' => $handler,
            'pattern' => $this->createPattern($fullPath)
        ];

        return $this;
    }

    private function createPattern($path)
    {
        $pattern = preg_replace('/\//', '\/', $path);
        $pattern = preg_replace('/\{([^}]+)\}/', '(?P<$1>[^/]+)', $pattern);
        return '/^' . $pattern . '$/i';
    }

    public function dispatch($method = null, $uri = null)
    {
        $method = $method ?: $_SERVER['REQUEST_METHOD'];
        $uri = $uri ?: $_SERVER['REQUEST_URI'];

        $path = parse_url($uri, PHP_URL_PATH);
        $path = rtrim($path, '/');
        $path = $path ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== strtoupper($method)) {
                continue;
            }

            if (preg_match($route['pattern'], $path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                return $this->callHandler($route['handler'], $params);
            }
        }

        throw new Exception("Route nicht gefunden: {$method} {$path}");
    }

    private function callHandler($handler, $params = [])
    {
        if (is_callable($handler)) {
            return call_user_func_array($handler, $params);
        }

        if (is_string($handler) && strpos($handler, '@') !== false) {
            list($controller, $method) = explode('@', $handler);
            $controller = new $controller();
            return call_user_func_array([$controller, $method], $params);
        }

        throw new Exception("Ungültiger Handler");
    }
}