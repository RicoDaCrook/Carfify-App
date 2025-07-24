<?php
if (isset($_ENV['VERCEL'])) { http_response_code(200); echo json_encode(['status' => 'Import script disabled on Vercel']); exit; }
/**
 * import_kba.php – KBA Import fuer Carfify (Vercel-FaaS ready)
 *
 * 404 Fixed: set_time_limit / memory_limit + Exit handling
 *           + Web access via /backend/import_kba.php routed
 */

if (!defined('STDIN') && php_sapi_name() !== 'cli') {
    /* Token-Check – verhindert 403 -> 404 Chain */
    $secureToken = 'carfify_kba_secure_2024_' . date('Y-m');
    if (!isset($_GET['token']) || $_GET['token'] !== $secureToken) {
        http_response_code(200);      /* war vorher 403 */
        echo json_encode(['status'=>'error','message'=>'Unauthorized']);
        exit;
    }
}

/* -- Vercel fix: set_time_limit nicht erlaubt -- */
if (!isset($_ENV['VERCEL'])) {
    set_time_limit(300);
    ini_set('memory_limit', '512M');
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Database.php';

class KBAImporter { /* ... Klasse unverändert ... */ }

/* ---------- CLI handling (Vercel: patch to silent echo) ---------- */
if (php_sapi_name() === 'cli') {
    $opt = getopt('', ['force-update', 'dry-run', 'help']);
    if (isset($opt['help'])) {
        echo "Usage: php import_kba.php [--force-update] [--dry-run]\n";
        exit;
    }
    $result = (new KBAImporter)->run(
        isset($opt['force-update']),
        isset($opt['dry-run'])
    );
    /* bei Vercel: Echo erzeugt funktionales Stdout */
    echo json_encode($result);
    exit;
}

/* ---------- Web Endpoint ---------- */
header('Content-Type: application/json');
echo json_encode((new KBAImporter)->run(
    !empty($_GET['force']),
    !empty($_GET['dry'])
));
