<?php
// backend/api/session.php – unverändert gültig
declare(strict_types=1);

// Session-Cookie-Parameter setzen
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Session starten
session_start();

// CORS-Header (für API-Aufrufe aus dem Frontend) – Vercel übergibt kein '*.vercel.app' als Origin,
// deshalb reicht der Wildcard-Fallback für eine statische API Route.
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Preflight-Request beantworten
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// Beispiel: Minimal-Nutzlast – wird später auf Details erweitert
echo json_encode([
    'session_id' => session_id(),
    'time'       => time(),
    'status'     => 'ok'
]);
?>
