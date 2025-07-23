<?php
/**
 * CORS-Headers für Vercel-Deployment von Carfify (Backend-API)
 * Wir erlauben explizit:
 *   – nur den in ENV konfigurierten Frontend-Origin (z. B. deine Vercel-URL)
 *   – GET / POST / OPTIONS
 *   – Content-Type und Authorization Header
 */

$allowedOrigin = $_ENV['FRONTEND_ORIGIN'] ?? 'https://carfify.vercel.app';

if ($_SERVER['HTTP_ORIGIN'] ?? '') {
    // Wildcardmatch nicht empfohlen, daher konkreter Origin
    if (preg_match('#^' . preg_quote($allowedOrigin, '#') . '$#i', $_SERVER['HTTP_ORIGIN'])) {
        header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    }
}

header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Option-Preflight sofort beenden
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
