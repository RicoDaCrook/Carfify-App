<?php
require_once '../config/init.php';

// CORS-Handling
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Nur POST-Anfragen erlauben
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Methode nicht erlaubt']);
    exit();
}

// Daten empfangen
$input = json_decode(file_get_contents('php://input'), true);

// Validierung
if (!isset($input['symptoms']) || !is_array($input['symptoms'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Symptome fehlen']);
    exit();
}

if (!isset($input['vehicle_id']) || !is_numeric($input['vehicle_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Fahrzeug-ID fehlt']);
    exit();
}

$symptoms = array_map('sanitizeInput', $input['symptoms']);
$vehicle_id = (int)$input['vehicle_id'];

// Session erstellen
$session_uuid = generateUUID();

try {
    // Fahrzeug abrufen
    $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
    $stmt->execute([$vehicle_id]);
    $vehicle = $stmt->fetch();
    
    if (!$vehicle) {
        throw new Exception('Fahrzeug nicht gefunden');
    }

    // Claude API aufrufen für Diagnose
    $diagnosis_prompt = "Als Kfz-Diagnoseexperte analysiere folgende Symptome für ein " . $vehicle['make'] . " " . $vehicle['model'] . " " . $vehicle['year'] . ":\n\n";
    $diagnosis_prompt .= "Symptome: " . implode(', ', $symptoms) . "\n\n";
    $diagnosis_prompt .= "Bitte gib eine detaillierte Diagnose mit:\n";
    $diagnosis_prompt .= "1. Wahrscheinliche Ursache\n";
    $diagnosis_prompt .= "2. Schweregrad (gering/mittel/schwer)\n";
    $diagnosis_prompt .= "3. Geschätzte Reparaturkosten in Euro\n";
    $diagnosis_prompt .= "4. Dringlichkeit\n";
    $diagnosis_prompt .= "5. Empfohlene Maßnahmen\n";
    $diagnosis_prompt .= "Antworte auf Deutsch und strukturiert.";

    // Simulierte Claude-Antwort
    $diagnosis_result = [
        'diagnosis' => 'Wahrscheinlich defekte Zündspule',
        'severity' => 'mittel',
        'estimated_cost' => 350.00,
        'urgency' => 'hoch',
        'recommended_action' => 'Sofortige Werkstattaufenthalt',
        'symptoms' => $symptoms
    ];

    // Session speichern
    $stmt = $pdo->prepare("INSERT INTO diagnosis_sessions (session_uuid, vehicle_id, symptoms, diagnosis_result, estimated_cost) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $session_uuid,
        $vehicle_id,
        json_encode($symptoms),
        json_encode($diagnosis_result),
        $diagnosis_result['estimated_cost']
    ]);

    // Erfolgsantwort
    echo json_encode([
        'success' => true,
        'session_uuid' => $session_uuid,
        'diagnosis' => $diagnosis_result,
        'vehicle' => $vehicle
    ]);

} catch (Exception $e) {
    logError('Diagnose-Fehler', ['error' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode(['error' => 'Diagnose konnte nicht erstellt werden']);
}