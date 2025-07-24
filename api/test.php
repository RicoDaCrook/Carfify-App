<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
echo json_encode([
    'status' => 'ok',
    'message' => 'PHP laeuft auf Vercel!',
    'env' => [
        'has_anthropic' => !empty($_ENV['ANTHROPIC_API_KEY']),
        'has_google' => !empty($_ENV['GOOGLE_MAPS_API_KEY']),
        'has_database' => !empty($_ENV['DATABASE_URL']),
        'has_gemini' => !empty($_ENV['GEMINI_API_KEY'])
    ],
    'php_version' => PHP_VERSION
]);
