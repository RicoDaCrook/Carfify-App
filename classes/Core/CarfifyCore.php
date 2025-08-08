<?php
/**
 * CarfifyCore - Hauptframework-Klasse für Carfify v4.0
 * Zentrale Anlaufstelle für alle Core-Funktionalitäten
 */

class CarfifyCore {
    private static $instance = null;
    private $config = [];
    private $database = null;
    private $session = null;
    
    // Singleton-Pattern
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // Privater Konstruktor
    private function __construct() {
        $this->init();
    }
    
    // Initialisierung
    private function init() {
        $this->loadConfig();
        $this->initDatabase();
        $this->initSession();
        $this->setupErrorHandling();
    }
    
    // Konfiguration laden
    private function loadConfig() {
        $configPath = __DIR__ . '/../../config.php';
        if (file_exists($configPath)) {
            $this->config = require $configPath;
        }
    }
    
    // Datenbank initialisieren
    private function initDatabase() {
        if (isset($this->config['database'])) {
            try {
                $dsn = sprintf(
                    'mysql:host=%s;dbname=%s;charset=utf8mb4',
                    $this->config['database']['host'],
                    $this->config['database']['name']
                );
                $this->database = new PDO($dsn, 
                    $this->config['database']['user'],
                    $this->config['database']['pass'],
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
            } catch (PDOException $e) {
                error_log('Database connection failed: ' . $e->getMessage());
            }
        }
    }
    
    // Session initialisieren
    private function initSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->session = &$_SESSION;
    }
    
    // Error Handling
    private function setupErrorHandling() {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
    }
    
    // Getter-Methoden
    public function getDatabase() {
        return $this->database;
    }
    
    public function getConfig($key = null) {
        if ($key === null) {
            return $this->config;
        }
        return $this->config[$key] ?? null;
    }
    
    public function getSession($key = null) {
        if ($key === null) {
            return $this->session;
        }
        return $this->session[$key] ?? null;
    }
    
    // Error Handler
    public function handleError($errno, $errstr, $errfile, $errline) {
        if (!(error_reporting() & $errno)) {
            return false;
        }
        
        $message = sprintf(
            'Error [%d]: %s in %s on line %d',
            $errno, $errstr, $errfile, $errline
        );
        
        error_log($message);
        
        if (ini_get('display_errors')) {
            echo $message;
        }
        
        return true;
    }
    
    // Exception Handler
    public function handleException($exception) {
        $message = sprintf(
            'Uncaught Exception: %s in %s on line %d',
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );
        
        error_log($message);
        
        if (ini_get('display_errors')) {
            echo $message;
        }
    }
    
    // Utility-Methoden
    public function sanitize($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitize'], $data);
        }
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
    
    public function redirect($url, $code = 302) {
        header(sprintf('Location: %s', $url), true, $code);
        exit();
    }
    
    public function isAjax() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    public function getCurrentUrl() {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . 
               "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    }
}