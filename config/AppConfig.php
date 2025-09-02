<?php

namespace Config;

class AppConfig
{
    private static $instance = null;
    private $config = [];
    
    private function __construct()
    {
        $this->loadConfig();
    }
    
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function loadConfig(): void
    {
        $this->config = [
            'app' => [
                'name' => $_ENV['APP_NAME'] ?? 'Carfify',
                'env' => $_ENV['APP_ENV'] ?? 'production',
                'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'url' => $_ENV['APP_URL'] ?? 'http://localhost',
                'timezone' => $_ENV['APP_TIMEZONE'] ?? 'Europe/Berlin',
            ],
            'database' => [
                'driver' => $_ENV['DB_DRIVER'] ?? 'mysql',
                'host' => $_ENV['DB_HOST'] ?? 'localhost',
                'port' => $_ENV['DB_PORT'] ?? '3306',
                'database' => $_ENV['DB_DATABASE'] ?? 'carfify',
                'username' => $_ENV['DB_USERNAME'] ?? 'root',
                'password' => $_ENV['DB_PASSWORD'] ?? '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
            ],
            'session' => [
                'name' => 'carfify_session',
                'lifetime' => 120,
                'expire_on_close' => false,
                'encrypt' => true,
                'files' => '/var/www/html/storage/sessions',
                'cookie' => 'carfify_session',
                'path' => '/',
                'domain' => null,
                'secure' => false,
                'http_only' => true,
            ],
            'cache' => [
                'default' => 'file',
                'stores' => [
                    'file' => [
                        'driver' => 'file',
                        'path' => '/var/www/html/storage/cache',
                    ],
                    'redis' => [
                        'driver' => 'redis',
                        'host' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
                        'password' => $_ENV['REDIS_PASSWORD'] ?? null,
                        'port' => $_ENV['REDIS_PORT'] ?? 6379,
                        'database' => 0,
                    ],
                ],
            ],
            'logging' => [
                'default' => 'single',
                'channels' => [
                    'single' => [
                        'driver' => 'single',
                        'path' => '/var/www/html/storage/logs/app.log',
                        'level' => 'debug',
                    ],
                    'daily' => [
                        'driver' => 'daily',
                        'path' => '/var/www/html/storage/logs/app.log',
                        'level' => 'debug',
                        'days' => 14,
                    ],
                ],
            ],
        ];
    }
    
    public function get(string $key, $default = null)
    {
        return $this->getNestedValue($this->config, $key, $default);
    }
    
    public function set(string $key, $value): void
    {
        $this->setNestedValue($this->config, $key, $value);
    }
    
    public function has(string $key): bool
    {
        return $this->getNestedValue($this->config, $key) !== null;
    }
    
    public function all(): array
    {
        return $this->config;
    }
    
    private function getNestedValue(array $array, string $key, $default = null)
    {
        $keys = explode('.', $key);
        
        foreach ($keys as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }
            $array = $array[$segment];
        }
        
        return $array;
    }
    
    private function setNestedValue(array &$array, string $key, $value): void
    {
        $keys = explode('.', $key);
        
        while (count($keys) > 1) {
            $key = array_shift($keys);
            
            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }
            
            $array = &$array[$key];
        }
        
        $array[array_shift($keys)] = $value;
    }
}