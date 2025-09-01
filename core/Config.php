<?php

namespace Core;

class Config
{
    private static $config = [];
    private static $initialized = false;

    private static function init()
    {
        if (self::$initialized) {
            return;
        }

        $configPath = dirname(__DIR__) . '/config/';
        
        // Lade Hauptkonfiguration
        if (file_exists($configPath . 'config.php')) {
            self::$config = require $configPath . 'config.php';
        }

        // Lade Umgebungs-spezifische Konfiguration
        $env = self::get('APP_ENV', 'production');
        $envFile = $configPath . 'config.' . $env . '.php';
        
        if (file_exists($envFile)) {
            $envConfig = require $envFile;
            self::$config = array_merge(self::$config, $envConfig);
        }

        self::$initialized = true;
    }

    public static function load()
    {
        self::init();
    }

    public static function get($key, $default = null)
    {
        self::init();
        
        $keys = explode('.', $key);
        $value = self::$config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    public static function set($key, $value)
    {
        self::init();
        
        $keys = explode('.', $key);
        $config = &self::$config;

        foreach ($keys as $k) {
            if (!isset($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }

        $config = $value;
    }

    public static function has($key)
    {
        self::init();
        return self::get($key) !== null;
    }

    public static function all()
    {
        self::init();
        return self::$config;
    }

    public static function reload()
    {
        self::$initialized = false;
        self::$config = [];
        self::init();
    }

    private static function loadConfig()
    {
        // Legacy-Methode für Rückwärtskompatibilität
        self::init();
    }
}