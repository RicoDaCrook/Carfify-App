<?php
/**
 * Carfify Core Class
 * 
 * Hauptklasse der Carfify Anwendung - Singleton Pattern
 * 
 * @package Carfify
 * @version 4.0
 */

class CarfifyCore
{
    /**
     * Singleton Instance
     * @var CarfifyCore|null
     */
    private static ?CarfifyCore $instance = null;
    
    /**
     * Database Instance
     * @var Database|null
     */
    private ?Database $db = null;
    
    /**
     * Router Instance
     * @var Router|null
     */
    private ?Router $router = null;
    
    /**
     * Auth Instance
     * @var Auth|null
     */
    private ?Auth $auth = null;
    
    /**
     * Logger Instance
     * @var Logger|null
     */
    private ?Logger $logger = null;
    
    /**
     * Cache Instance
     * @var Cache|null
     */
    private ?Cache $cache = null;
    
    /**
     * Security Instance
     * @var Security|null
     */
    private ?Security $security = null;
    
    /**
     * Application Configuration
     * @var array
     */
    private array $config = [];
    
    /**
     * Private Constructor - Singleton Pattern
     */
    private function __construct()
    {
        $this->initialize();
    }
    
    /**
     * Get Singleton Instance
     * 
     * @return CarfifyCore
     */
    public static function getInstance(): CarfifyCore
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Prevent cloning of the instance
     */
    private function __clone() {}
    
    /**
     * Prevent unserializing of the instance
     */
    public function __wakeup() {}
    
    /**
     * Initialize the application
     */
    private function initialize(): void
    {
        try {
            // Load configuration
            $this->loadConfiguration();
            
            // Initialize core components
            $this->initializeDatabase();
            $this->initializeLogger();
            $this->initializeCache();
            $this->initializeSecurity();
            $this->initializeAuth();
            $this->initializeRouter();
            
            // Set up error handler
            $this->setupErrorHandler();
            
            // Log application start
            $this->logger->info('Carfify application initialized');
            
        } catch (Exception $e) {
            error_log('Failed to initialize Carfify: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Load application configuration
     */
    private function loadConfiguration(): void
    {
        $this->config = [
            'db' => DB_CONFIG,
            'security' => SECURITY_CONFIG,
            'upload' => UPLOAD_CONFIG,
            'cache' => CACHE_CONFIG,
            'api' => API_CONFIG,
            'log' => LOG_CONFIG,
            'environment' => ENVIRONMENT,
            'base_path' => BASE_PATH,
            'base_url' => BASE_URL,
        ];
    }
    
    /**
     * Initialize database connection
     */
    private function initializeDatabase(): void
    {
        $this->db = new Database($this->config['db']);
    }
    
    /**
     * Initialize logger
     */
    private function initializeLogger(): void
    {
        $this->logger = new Logger($this->config['log']);
    }
    
    /**
     * Initialize cache
     */
    private function initializeCache(): void
    {
        $this->cache = new Cache($this->config['cache']);
    }
    
    /**
     * Initialize security
     */
    private function initializeSecurity(): void
    {
        $this->security = new Security($this->config['security']);
    }
    
    /**
     * Initialize authentication
     */
    private function initializeAuth(): void
    {
        $this->auth = new Auth($this->db, $this->security);
    }
    
    /**
     * Initialize router
     */
    private function initializeRouter(): void
    {
        $this->router = new Router($this->config['base_url']);
    }
    
    /**
     * Setup error handler
     */
    private function setupErrorHandler(): void
    {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }
    
    /**
     * Handle errors
     * 
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     * @return bool
     */
    public function handleError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        $error = [
            'type' => $errno,
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline,
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS),
        ];
        
        $this->logger->error('PHP Error', $error);
        
        if (ENVIRONMENT === 'development') {
            return false; // Let PHP handle the error
        }
        
        return true;
    }
    
    /**
     * Handle exceptions
     * 
     * @param Throwable $exception
     */
    public function handleException(Throwable $exception): void
    {
        $error = [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTrace(),
        ];
        
        $this->logger->error('Uncaught Exception', $error);
        
        if (ENVIRONMENT === 'development') {
            echo '<pre>';
            print_r($error);
            echo '</pre>';
        } else {
            http_response_code(500);
            echo 'Ein interner Serverfehler ist aufgetreten.';
        }
    }
    
    /**
     * Handle shutdown
     */
    public function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $this->logger->error('Fatal Error', $error);
        }
    }
    
    /**
     * Get database instance
     * 
     * @return Database
     */
    public function getDatabase(): Database
    {
        return $this->db;
    }
    
    /**
     * Get router instance
     * 
     * @return Router
     */
    public function getRouter(): Router
    {
        return $this->router;
    }
    
    /**
     * Get auth instance
     * 
     * @return Auth
     */
    public function getAuth(): Auth
    {
        return $this->auth;
    }
    
    /**
     * Get logger instance
     * 
     * @return Logger
     */
    public function getLogger(): Logger
    {
        return $this->logger;
    }
    
    /**
     * Get cache instance
     * 
     * @return Cache
     */
    public function getCache(): Cache
    {
        return $this->cache;
    }
    
    /**
     * Get security instance
     * 
     * @return Security
     */
    public function getSecurity(): Security
    {
        return $this->security;
    }
    
    /**
     * Get configuration
     * 
     * @param string|null $key
     * @return mixed
     */
    public function getConfig(?string $key = null)
    {
        if ($key === null) {
            return $this->config;
        }
        
        return $this->config[$key] ?? null;
    }
    
    /**
     * Run the application
     */
    public function run(): void
    {
        try {
            $this->router->dispatch();
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Get application version
     * 
     * @return string
     */
    public function getVersion(): string
    {
        return '4.0.0';
    }
    
    /**
     * Check if application is in development mode
     * 
     * @return bool
     */
    public function isDevelopment(): bool
    {
        return $this->config['environment'] === 'development';
    }
    
    /**
     * Check if application is in production mode
     * 
     * @return bool
     */
    public function isProduction(): bool
    {
        return $this->config['environment'] === 'production';
    }
}