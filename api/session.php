<?php
require_once '../config/init.php';

// GET-Session-Details
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Methode nicht erlaubt']);
    exit();
}

$session_uuid = isset($_GET['uuid']) ? sanitizeInput($_GET['uuid']) : null;

if (!$session_uuid) {
    http_response_code(400);
    echo json_encode(['error' => 'Session-ID fehlt']);
    exit();
}

try {
    // Session mit Fahrzeugdaten abrufen
    $stmt = $pdo->prepare("SELECT ds.*, v.* FROM diagnosis_sessions ds JOIN vehicles v ON ds.vehicle_id = v.id WHERE ds.session_uuid = ?");
    $stmt->execute([$session_uuid]);
    $session = $stmt->fetch();

    if (!$session) {
        http_response_code(404);
        echo json_encode(['error' => 'Session nicht gefunden']);
        exit();
    }

    echo json_encode([
        'success' => true,
        'session' => $session,
        'diagnosis' => json_decode($session['diagnosis_result'], true)
    ]);

} catch (Exception $e) {
    logError('Session-Abruf Fehler', ['error' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode(['error' => 'Session konnte nicht geladen werden']);
}