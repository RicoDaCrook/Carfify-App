<?php
// API Keys Configuration
return [
    'google_maps' => [
        'api_key' => $_ENV['GOOGLE_MAPS_API_KEY'] ?? 'YOUR_GOOGLE_MAPS_API_KEY_HERE',
        'places_api_url' => 'https://maps.googleapis.com/maps/api/place/',
        'embed_url' => 'https://www.google.com/maps/embed/v1/place'
    ],
    
    'gemini' => [
        'api_key' => $_ENV['GEMINI_API_KEY'] ?? 'YOUR_GEMINI_API_KEY_HERE',
        'base_url' => 'https://generativelanguage.googleapis.com/v1beta/models/'
    ],
    
    'cache' => [
        'workshop_data_ttl' => 3600, // 1 Stunde
        'reviews_ttl' => 86400, // 24 Stunden
        'cache_dir' => 'cache/workshops/'
    ]
];