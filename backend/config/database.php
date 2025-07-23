<?php
/**
 * /backend/api/vehicles.php
 *
 * REST-Endpunkt: GET /vehicles?hsn=yyy&tsn=xxx
 * Liefert passende Fahrzeuge aus der PostgreSQL-Herstellerschlüssel-DB
 *
 * Sicherheitsmechanismen
 *   • Nur akzeptierte HTTP-Methoden (GET)
 *   • Input-Sanitizing & Type-Casting
 *   • Rate-Limit-Schtuz via Redis-Token-Bucket (optional)
 *   • Cache-Headers + CORS korrekt gesetzt
 *
 * @package Carfify
 * @since   1.0.0
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../config/database.php';

use CarfifyDatabase as DB;

// --------------------------------------------------
// 1) Sicherheits- & CORS-Headers
// --------------------------------------------------
require_once __DIR__ . '/../security/cors.php';

if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit(json_encode(['error' => 'Only GET allowed']));
}

// --------------------------------------------------
// 2) Input-Validierung
// --------------------------------------------------
$hsn = ltrim(trim($_GET['hsn'] ?? ''), '0');
$tsn = ltrim(trim($_GET['tsn'] ?? ''), '0');

if ($hsn === '' || !preg_match('/^\d{2,4}$/', $hsn)) {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid HSN parameter']));
}

if ($tsn === '' || !preg_match('/^[A-Za-z0-9]{1,3}$/', $tsn)) {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid TSN parameter']));
}

// --------------------------------------------------
// 3) Business-Logik
// --------------------------------------------------
/**
 * 3.1  Bei identischem HSN/TSN können mehrere Varianten anfallen
 *      → Sobald wir pro KaufBerater eine FIN-Datenbank haben,
 *        wird die treibereinzeln abgeglichen. 1.0.0 fokussiert auf KBA.
 * 3.2  Performance: Resultat für 10 Min. via APCu-Cache halten.
 */
$cacheKey = sprintf('vehicles_%s_%s', $hsn, $tsn);
$cached = apcu_fetch($cacheKey);
if ($cached !== false) {
    echo $cached;
    exit;
}

// Datenbankabfrage
$sql = '
    SELECT
        id,
        hsn,
        tsn,
        make       AS hersteller,
        model      AS modell,
        variant    AS ausfuehrung,
        engine,
        power_kw,
        power_ps,
        fuel_type,
        year_from,
        year_to
    FROM   vehicles
    WHERE  hsn = ?
      AND  tsn = ?
    ORDER BY year_from ASC
';

try {
    $rows = DB::select($sql, [$hsn, strtoupper($tsn)]);
} catch (Throwable $e) {
    http_response_code(500);
    error_log('DB Error vehicles.php: ' . $e->getMessage());
    exit(json_encode(['error' => 'Database unavailable']));
}

// --------------------------------------------------
// 4) Response-Normalisierung
// --------------------------------------------------
$response = [
    'hsn' => $hsn,
    'tsn' => $tsn,
    'count' => count($rows),
    'vehicles' => $rows
];

$json = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
if ($json === false) {
    http_response_code(500);
    exit(json_encode(['error' => 'JSON encoding failed']));
}

// 4.1 Cache speichern
apcu_store($cacheKey, $json, 600); // 10 Min

echo $json;
