<?php
/**
 * Carfify API Base Class
 * Phase 1.2 - API Foundation
 */

class CarfifyAPI {
    private $db;
    private $rateLimiter;
    private $sessionManager;
    
    public function __construct() {
        $this->init();
    }
    
    private function init() {
        // Set headers
        $this->setHeaders();
        
        // Initialize components
        $this->initDatabase();
        $this->initRateLimiter();
        $this->initSessionManager();
        $this->initErrorHandler();
    }
    
    private function setHeaders() {
        // CORS headers
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Max-Age: 86400');
        
        // Content type
        header('Content-Type: application/json; charset=utf-8');
        
        // Security headers
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        
        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }
    
    private function initDatabase() {
        try {
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $dbname = $_ENV['DB_NAME'] ?? 'carfify';
            $username = $_ENV['DB_USER'] ?? 'root';
            $password = $_ENV['DB_PASS'] ?? '';
            
            $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
            $this->db = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            $this->error('Database connection failed', 500);
        }
    }
    
    private function initRateLimiter() {
        $this->rateLimiter = new RateLimiter($this->db);
    }
    
    private function initSessionManager() {
        $this->sessionManager = new SessionManager($this->db);
    }
    
    private function initErrorHandler() {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }
    
    public function handleRequest() {
        try {
            // Rate limiting
            $this->rateLimiter->checkLimit();
            
            // Get request data
            $method = $_SERVER['REQUEST_METHOD'];
            $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $path = str_replace('/api', '', $path);
            
            // Route request
            $this->route($method, $path);
            
        } catch (Exception $e) {
            $this->error($e->getMessage(), $e->getCode() ?: 500);
        }
    }
    
    private function route($method, $path) {
        $routes = [
            'GET' => [
                '/diagnose' => 'DiagnoseController@index',
                '/diagnose/(\d+)' => 'DiagnoseController@show',
                '/sell' => 'SellController@index',
                '/sell/estimate' => 'SellController@estimate',
            ],
            'POST' => [
                '/diagnose' => 'DiagnoseController@store',
                '/sell/estimate' => 'SellController@calculate',
            ],
            'PUT' => [
                '/diagnose/(\d+)' => 'DiagnoseController@update',
            ],
            'DELETE' => [
                '/diagnose/(\d+)' => 'DiagnoseController@destroy',
            ]
        ];
        
        if (!isset($routes[$method])) {
            $this->error('Method not allowed', 405);
        }
        
        foreach ($routes[$method] as $route => $handler) {
            $pattern = '#^' . preg_replace('/\{(\w+)\}/', '(\w+)', $route) . '$#';
            if (preg_match($pattern, $path, $matches)) {
                array_shift($matches);
                $this->callController($handler, $matches);
                return;
            }
        }
        
        $this->error('Endpoint not found', 404);
    }
    
    private function callController($handler, $params = []) {
        list($controller, $method) = explode('@', $handler);
        $controllerClass = $controller . 'Controller';
        
        if (!class_exists($controllerClass)) {
            $this->error('Controller not found', 500);
        }
        
        $controllerInstance = new $controllerClass($this->db);
        
        if (!method_exists($controllerInstance, $method)) {
            $this->error('Method not found', 500);
        }
        
        // Get request body
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        // Call method with params and input
        $result = call_user_func_array([$controllerInstance, $method], [$params, $input]);
        
        $this->response($result);
    }
    
    public function response($data, $status = 200) {
        http_response_code($status);
        echo json_encode([
            'success' => $status < 400,
            'data' => $data,
            'timestamp' => time()
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    public function error($message, $status = 400) {
        http_response_code($status);
        echo json_encode([
            'success' => false,
            'error' => [
                'message' => $message,
                'code' => $status
            ],
            'timestamp' => time()
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    public function handleError($errno, $errstr, $errfile, $errline) {
        if (!(error_reporting() & $errno)) {
            return false;
        }
        
        $this->error("Error: {$errstr} in {$errfile}:{$errline}", 500);
    }
    
    public function handleException(Throwable $e) {
        $this->error($e->getMessage(), $e->getCode() ?: 500);
    }
    
    public function handleShutdown() {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $this->error("Fatal error: {$error['message']}", 500);
        }
    }
}

// Rate Limiter Class
class RateLimiter {
    private $db;
    private $limit = 100; // requests per hour
    private $window = 3600; // seconds
    
    public function __construct($db) {
        $this->db = $db;
        $this->initTable();
    }
    
    private function initTable() {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS rate_limits (
                ip VARCHAR(45) NOT NULL,
                requests INT DEFAULT 1,
                reset_time INT NOT NULL,
                PRIMARY KEY (ip)
            )
        ");
    }
    
    public function checkLimit() {
        $ip = $this->getClientIP();
        $now = time();
        
        // Clean old entries
        $stmt = $this->db->prepare("DELETE FROM rate_limits WHERE reset_time < ?");
        $stmt->execute([$now]);
        
        // Get current count
        $stmt = $this->db->prepare("SELECT requests, reset_time FROM rate_limits WHERE ip = ?");
        $stmt->execute([$ip]);
        $row = $stmt->fetch();
        
        if (!$row) {
            // First request
            $stmt = $this->db->prepare("INSERT INTO rate_limits (ip, reset_time) VALUES (?, ?)");
            $stmt->execute([$ip, $now + $this->window]);
            return true;
        }
        
        if ($row['reset_time'] < $now) {
            // Reset window
            $stmt = $this->db->prepare("UPDATE rate_limits SET requests = 1, reset_time = ? WHERE ip = ?");
            $stmt->execute([$now + $this->window, $ip]);
            return true;
        }
        
        if ($row['requests'] >= $this->limit) {
            throw new Exception('Rate limit exceeded', 429);
        }
        
        // Increment counter
        $stmt = $this->db->prepare("UPDATE rate_limits SET requests = requests + 1 WHERE ip = ?");
        $stmt->execute([$ip]);
        
        // Set headers
        header('X-RateLimit-Limit: ' . $this->limit);
        header('X-RateLimit-Remaining: ' . ($this->limit - $row['requests'] - 1));
        header('X-RateLimit-Reset: ' . $row['reset_time']);
    }
    
    private function getClientIP() {
        $headers = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                return trim($ip);
            }
        }
        
        return '127.0.0.1';
    }
}

// Session Manager Class
class SessionManager {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
        $this->initSessions();
    }
    
    private function initSessions() {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
        ini_set('session.cookie_samesite', 'Strict');
        
        session_set_save_handler(
            [$this, 'open'],
            [$this, 'close'],
            [$this, 'read'],
            [$this, 'write'],
            [$this, 'destroy'],
            [$this, 'gc']
        );
        
        session_start();
    }
    
    public function open($savePath, $sessionName) {
        return true;
    }
    
    public function close() {
        return true;
    }
    
    public function read($id) {
        $stmt = $this->db->prepare("SELECT data FROM sessions WHERE id = ? AND expire > ?");
        $stmt->execute([$id, time()]);
        $row = $stmt->fetch();
        return $row ? $row['data'] : '';
    }
    
    public function write($id, $data) {
        $expire = time() + (int)ini_get('session.gc_maxlifetime');
        $stmt = $this->db->prepare("
            INSERT INTO sessions (id, data, expire) VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE data = VALUES(data), expire = VALUES(expire)
        ");
        return $stmt->execute([$id, $data, $expire]);
    }
    
    public function destroy($id) {
        $stmt = $this->db->prepare("DELETE FROM sessions WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    public function gc($maxlifetime) {
        $stmt = $this->db->prepare("DELETE FROM sessions WHERE expire < ?");
        return $stmt->execute([time()]);
    }
}

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        list($key, $value) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

// Initialize API
$api = new CarfifyAPI();
$api->handleRequest();
?>