<?php

namespace Config;

/**
 * Configuration Management Class
 * Handles all application-wide configuration settings
 */
class Config
{
    private static $instance = null;
    private $config = [];
    
    private function __construct()
    {
        $this->loadConfig();
    }
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function loadConfig()
    {
        $this->config = [
            'database' => [
                'host' => $_ENV['DB_HOST'] ?? 'localhost',
                'name' => $_ENV['DB_NAME'] ?? 'carfify',
                'user' => $_ENV['DB_USER'] ?? 'root',
                'pass' => $_ENV['DB_PASS'] ?? '',
                'charset' => 'utf8mb4'
            ],
            'app' => [
                'name' => 'Carfify v4.0',
                'version' => '4.0.0',
                'debug' => $_ENV['APP_DEBUG'] ?? false,
                'base_url' => $_ENV['APP_URL'] ?? 'http://localhost'
            ],
            'security' => [
                'jwt_secret' => $_ENV['JWT_SECRET'] ?? 'your-secret-key-change-this',
                'session_timeout' => 3600
            ]
        ];
    }
    
    public function get($key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $this->config;
        
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }
    
    public function set($key, $value)
    {
        $keys = explode('.', $key);
        $config = &$this->config;
        
        foreach ($keys as $k) {
            if (!isset($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }
        
        $config = $value;
    }
    
    public function all()
    {
        return $this->config;
    }
}