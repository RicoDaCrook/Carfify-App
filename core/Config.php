<?php
/**
 * Carfify v4.0 - Configuration Management
 * Enterprise Grade Configuration Handler
 */

declare(strict_types=1);

namespace Core;

class Config
{
    private static array $config = [];
    private static bool $loaded = false;
    
    /**
     * Load all configuration files
     * @throws \Exception
     */
    public static function load(): void
    {
        if (self::$loaded) {
            return;
        }
        
        $configDir = APP_ROOT . 'config' . DIRECTORY_SEPARATOR;
        
        if (!is_dir($configDir)) {
            throw new \Exception('Configuration directory not found: ' . $configDir);
        }
        
        // Load base configuration files in order
        $configFiles = [
            'app.php',
            'database.php',
            'cache.php',
            'logging.php',
            'security.php'
        ];
        
        foreach ($configFiles as $file) {
            $filePath = $configDir . $file;
            if (file_exists($filePath)) {
                $config = require $filePath;
                if (is_array($config)) {
                    self::$config = array_merge(self::$config, $config);
                }
            }
        }
        
        // Load environment-specific config
        $env = $_ENV['APP_ENV'] ?? 'production';
        $envFile = $configDir . $env . '.php';
        
        if (file_exists($envFile)) {
            $config = require $envFile;
            if (is_array($config)) {
                self::$config = array_merge(self::$config, $config);
            }
        }
        
        // Load .env file if exists
        self::loadEnvFile();
        
        self::$loaded = true;
    }
    
    /**
     * Get configuration value
     * @param string $key Dot notation key (e.g., 'database.host')
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        if (!self::$loaded) {
            self::load();
        }
        
        $keys = explode('.', $key);
        $value = self::$config;
        
        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }
    
    /**
     * Set configuration value
     * @param string $key Dot notation key
     * @param mixed $value Value to set
     */
    public static function set(string $key, $value): void
    {
        if (!self::$loaded) {
            self::load();
        }
        
        $keys = explode('.', $key);
        $config = &self::$config;
        
        foreach ($keys as $k) {
            if (!isset($config[$k]) || !is_array($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }
        
        $config = $value;
    }
    
    /**
     * Check if configuration key exists
     * @param string $key Dot notation key
     * @return bool
     */
    public static function has(string $key): bool
    {
        return self::get($key) !== null;
    }
    
    /**
     * Get all configuration
     * @return array
     */
    public static function all(): array
    {
        if (!self::$loaded) {
            self::load();
        }
        
        return self::$config;
    }
    
    /**
     * Load .env file
     */
    private static function loadEnvFile(): void
    {
        $envFile = APP_ROOT . '.env';
        
        if (!file_exists($envFile)) {
            return;
        }
        
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            
            if (!array_key_exists($name, $_ENV)) {
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
                putenv("{$name}={$value}");
            }
        }
    }
}