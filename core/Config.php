<?php

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
        $configFile = __DIR__ . '/../config/config.php';
        if (file_exists($configFile)) {
            $this->config = include $configFile;
        } else {
            $this->config = [
                'db' => [
                    'host' => 'localhost',
                    'name' => 'carfify',
                    'user' => 'root',
                    'pass' => ''
                ],
                'app' => [
                    'debug' => true,
                    'base_url' => 'http://localhost/carfify'
                ]
            ];
        }
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
}