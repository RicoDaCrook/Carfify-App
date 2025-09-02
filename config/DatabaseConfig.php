<?php

namespace Config;

class DatabaseConfig
{
    private static $instance = null;
    private $config;
    
    private function __construct()
    {
        $this->config = AppConfig::getInstance()->get('database');
    }
    
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getDsn(): string
    {
        return sprintf(
            '%s:host=%s;port=%s;dbname=%s;charset=%s',
            $this->config['driver'],
            $this->config['host'],
            $this->config['port'],
            $this->config['database'],
            $this->config['charset']
        );
    }
    
    public function getUsername(): string
    {
        return $this->config['username'];
    }
    
    public function getPassword(): string
    {
        return $this->config['password'];
    }
    
    public function getOptions(): array
    {
        return [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ];
    }
    
    public function getConfig(): array
    {
        return $this->config;
    }
}