<?php

namespace Config;

class AppConfig {
    const DB_HOST = 'localhost';
    const DB_NAME = 'carfify';
    const DB_USER = 'root';
    const DB_PASS = '';
    
    const SITE_NAME = 'Carfify';
    const SITE_URL = 'https://your-domain.com';
    
    const AI_API_KEY = 'your-ai-api-key';
    const GOOGLE_MAPS_API = 'your-google-maps-api';
    
    public static function get($key) {
        $constants = [
            'DB_HOST' => self::DB_HOST,
            'DB_NAME' => self::DB_NAME,
            'SITE_NAME' => self::SITE_NAME,
            'SITE_URL' => self::SITE_URL,
        ];
        
        return $constants[$key] ?? null;
    }
}

// Datenbank-Verbindung
function getDB() {
    static $db = null;
    if ($db === null) {
        $db = new PDO(
            'mysql:host=' . AppConfig::DB_HOST . ';dbname=' . AppConfig::DB_NAME,
            AppConfig::DB_USER,
            AppConfig::DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }
    return $db;
}
?>