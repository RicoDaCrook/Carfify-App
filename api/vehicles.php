<?php
/**
 * vehicles.php
 * Endpunkt zur Suche nach Fahrzeugen über HSN/TSN oder Freitext.
 * Liefert JSON-Array mit passenden Fahrzeugen.
 */

header('Content-Type: application/json; charset=utf-8');

// CORS erlauben
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// OPTIONS-Request sofort beantworten
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Datenbankverbindung
require_once __DIR__ . '/../config/database.php';

// Parameter auslesen
$hsn = isset($_GET['hsn']) ? trim($_GET['hsn']) : '';
$tsn = isset($_GET['tsn']) ? trim($_GET['tsn']) : '';
$text = isset($_GET['text']) ? trim($_GET['text']) : '';

$vehicles = [];

try {
    $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    if ($hsn !== '' && $tsn !== '') {
        // Suche nach HSN/TSN
        $stmt = $pdo->prepare(
            "SELECT id, hsn, tsn, marke, modell, baujahr, kraftstoff, leistung_ps, hubraum_ccm
             FROM vehicles
             WHERE hsn = :hsn AND tsn = :tsn
             ORDER BY baujahr DESC"
        );
        $stmt->execute([':hsn' => $hsn, ':tsn' => $tsn]);
        $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($text !== '') {
        // Freitextsuche
        $like = '%' . $text . '%';
        $stmt = $pdo->prepare(
            "SELECT id, hsn, tsn, marke, modell, baujahr, kraftstoff, leistung_ps, hubraum_ccm
             FROM vehicles
             WHERE marke ILIKE :like OR modell ILIKE :like
             ORDER BY marke, modell, baujahr DESC
             LIMIT 50"
        );
        $stmt->execute([':like' => $like]);
        $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Parameter hsn/tsn oder text erforderlich.']);
        exit();
    }

    echo json_encode($vehicles);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Datenbankfehler: ' . $e->getMessage()]);
}