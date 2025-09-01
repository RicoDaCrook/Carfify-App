<?php
/**
 * Carfify Configuration Class
 * 
 * @package Core
 * @version 4.0
 */

namespace Core;

/**
 * Singleton Configuration Class
 */
class Config
{
    private static $instance = null;
    private $config = [];
    private $configPath;
    
    private function __construct()
    {
        $this->configPath = APP_ROOT . '/config';
        $this->loadDefaults();
        $this->loadEnvironmentConfig();
    }
    
    /**
     * Get singleton instance
     * 
     * @return Config
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Load default configuration
     */
    private function loadDefaults()
    {
        $this->config = [
            'app' => [
                'name' => 'Carfify v4.0',
                'version' => '4.0.0',
                'debug' => true,
                'timezone' => 'Europe/Berlin'
            ],
            'database' => [
                'driver' => 'mysql',
                'host' => 'localhost',
                'port' => 3306,
                'charset' => 'utf8mb4'
            ],
            'paths' => [
                'root' => APP_ROOT,
                'core' => CORE_PATH,
                'config' => APP_ROOT . '/config',
                'logs' => APP_ROOT . '/logs',
                'cache' => APP_ROOT . '/cache'
            ]
        ];
    }
    
    /**
     * Load environment specific configuration
     */
    private function loadEnvironmentConfig()
    {
        $env = $this->getEnvironment();
        $envFile = $this->configPath . '/' . $env . '.php';
        
        if (file_exists($envFile)) {
            $envConfig = require $envFile;
            $this->config = array_merge_recursive($this->config, $envConfig);
        }
        
        // Load .env file if exists
        $this->loadDotEnv();
    }
    
    /**
     * Load .env file
     */
    private function loadDotEnv()
    {
        $envFile = APP_ROOT . '/.env';
        
        if (!file_exists($envFile)) {
            return;
        }
        
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            if (strpos($line, '#') === 0) {
                continue;
            }
            
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes
            $value = trim($value, '"\'');
            
            // Set as environment variable
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
    
    /**
     * Get configuration value
     * 
     * @param string $key Dot notation key (e.g. 'database.host')
     * @param mixed $default Default value if key not found
     * @return mixed
     */
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
    
    /**
     * Set configuration value
     * 
     * @param string $key Dot notation key
     * @param mixed $value Value to set
     */
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
    
    /**
     * Get all configuration
     * 
     * @return array
     */
    public function all()
    {
        return $this->config;
    }
    
    /**
     * Get current environment
     * 
     * @return string
     */
    public function getEnvironment()
    {
        return $_ENV['APP_ENV'] ?? 'development';
    }
    
    /**
     * Check if debug mode is enabled
     * 
     * @return bool
     */
    public function isDebug()
    {
        return $this->get('app.debug', false);
    }
    
    // Prevent cloning and unserialization
    private function __clone() {}
    public function __wakeup() {}
}