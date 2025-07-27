<?php
/**
 * session.php
 * Endpunkt zum Speichern und Laden des Diagnose-Fortschritts.
 * Erwartet POST-JSON mit action ('save' oder 'load') und ggf. session_data.
 * Gibt Erfolg oder gespeicherte Daten zurück.
 */

header('Content-Type: application/json; charset=utf-8');

// CORS erlauben
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// OPTIONS-Request sofort beantworten
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Nur POST erlauben
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Nur POST erlaubt.']);
    exit();
}

// JSON-Body einlesen
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['error' => 'Ungültiges JSON.']);
    exit();
}

$action = $input['action'] ?? '';
$session_id = $input['session_id'] ?? uniqid('sess_', true);

// Datenbankverbindung
require_once __DIR__ . '/../config/database.php';

$pdo = new PDO(DB_DSN, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

if ($action === 'save') {
    $session_data = json_encode($input['session_data'] ?? []);
    $stmt = $pdo->prepare(
        "INSERT INTO sessions (session_id, session_data, updated_at)
         VALUES (:sid, :data, NOW())
         ON CONFLICT (session_id) DO UPDATE SET session_data = EXCLUDED.session_data, updated_at = NOW()"
    );
    $stmt->execute([':sid' => $session_id, ':data' => $session_data]);
    echo json_encode(['success' => true, 'session_id' => $session_id]);

} elseif ($action === 'load') {
    $stmt = $pdo->prepare("SELECT session_data FROM sessions WHERE session_id = :sid");
    $stmt->execute([':sid' => $session_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        echo json_encode(['session_data' => json_decode($row['session_data'], true)]);
    } else {
        echo json_encode(['session_data' => null]);
    }

} else {
    http_response_code(400);
    echo json_encode(['error' => 'action muss save oder load sein.']);
}