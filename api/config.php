<?php
/**
 * Carfify API Configuration
 * Phase 1.2 - Configuration Settings
 */

return [
    // Database Configuration
    'database' => [
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'port' => $_ENV['DB_PORT'] ?? '3306',
        'name' => $_ENV['DB_NAME'] ?? 'carfify',
        'username' => $_ENV['DB_USER'] ?? 'root',
        'password' => $_ENV['DB_PASS'] ?? '',
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ],
    ],
    
    // API Configuration
    'api' => [
        'version' => '1.0',
        'base_url' => $_ENV['API_BASE_URL'] ?? 'https://carfify.de/api',
        'rate_limit' => [
            'requests' => 100,
            'window' => 3600, // 1 hour
        ],
        'cors' => [
            'allowed_origins' => explode(',', $_ENV['CORS_ORIGINS'] ?? '*'),
            'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
            'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
        ],
    ],
    
    // Security Configuration
    'security' => [
        'jwt_secret' => $_ENV['JWT_SECRET'] ?? bin2hex(random_bytes(32)),
        'jwt_expiry' => 3600, // 1 hour
        'session_lifetime' => 86400, // 24 hours
        'password_min_length' => 8,
        'max_login_attempts' => 5,
        'lockout_duration' => 900, // 15 minutes
    ],
    
    // External APIs
    'external' => [
        'mobile_de' => [
            'api_key' => $_ENV['MOBILE_DE_API_KEY'] ?? '',
            'base_url' => 'https://services.mobile.de/search-api',
        ],
        'google_maps' => [
            'api_key' => $_ENV['GOOGLE_MAPS_API_KEY'] ?? '',
        ],
        'weather' => [
            'api_key' => $_ENV['OPENWEATHER_API_KEY'] ?? '',
            'base_url' => 'https://api.openweathermap.org/data/2.5',
        ],
    ],
    
    // Cache Configuration
    'cache' => [
        'driver' => $_ENV['CACHE_DRIVER'] ?? 'file',
        'ttl' => 3600, // 1 hour
        'redis' => [
            'host' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
            'port' => $_ENV['REDIS_PORT'] ?? 6379,
            'password' => $_ENV['REDIS_PASSWORD'] ?? null,
        ],
    ],
    
    // Logging Configuration
    'logging' => [
        'level' => $_ENV['LOG_LEVEL'] ?? 'info',
        'file' => __DIR__ . '/../logs/app.log',
        'max_files' => 5,
        'max_size' => '10MB',
    ],
    
    // Upload Configuration
    'upload' => [
        'max_file_size' => 5 * 1024 * 1024, // 5MB
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf'],
        'upload_path' => __DIR__ . '/../uploads/',
        'url_path' => '/uploads/',
    ],
    
    // Email Configuration
    'email' => [
        'driver' => $_ENV['MAIL_DRIVER'] ?? 'smtp',
        'host' => $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com',
        'port' => $_ENV['MAIL_PORT'] ?? 587,
        'username' => $_ENV['MAIL_USERNAME'] ?? '',
        'password' => $_ENV['MAIL_PASSWORD'] ?? '',
        'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
        'from' => [
            'address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@carfify.de',
            'name' => $_ENV['MAIL_FROM_NAME'] ?? 'Carfify',
        ],
    ],
    
    // Feature Flags
    'features' => [
        'diagnose' => true,
        'sell' => true,
        'maintenance' => false,
        'parts' => false,
        'reviews' => false,
        'forum' => false,
        'insurance' => false,
        'tuv' => false,
    ],
];
?>