<?php
/**
 * Carfify Datenbank-Konfiguration
 * Stellt die Verbindung zur PostgreSQL-Datenbank her
 */

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        $host = getenv('DB_HOST') ?: 'localhost';
        $port = getenv('DB_PORT') ?: '5432';
        $database = getenv('DB_NAME') ?: 'carfify';
        $username = getenv('DB_USER') ?: 'postgres';
        $password = getenv('DB_PASSWORD') ?: '';
        
        try {
            $dsn = "pgsql:host={$host};port={$port};dbname={$database}";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => true,
            ];
            
            $this->connection = new PDO($dsn, $username, $password, $options);
            
            // UTF-8 Encoding sicherstellen
            $this->connection->exec("SET NAMES 'utf8'");
            
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            
            if (getenv('ENVIRONMENT') === 'development') {
                throw new Exception('Database connection failed: ' . $e->getMessage());
            } else {
                throw new Exception('Database connection failed');
            }
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }
    
    public function lastInsertId($name = null) {
        return $this->connection->lastInsertId($name);
    }
    
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    public function commit() {
        return $this->connection->commit();
    }
    
    public function rollBack() {
        return $this->connection->rollBack();
    }
    
    // Verhindern, dass die Instanz geklont wird
    private function __clone() {}
    
    // Verhindern, dass die Instanz deserialisiert wird
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Kurzfunktion für Datenbankzugriff
function db() {
    return Database::getInstance()->getConnection();
}