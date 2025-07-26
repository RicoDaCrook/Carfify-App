<?php
require_once '../config/init.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        handleDiagnosis($_POST);
        break;
    case 'GET':
        getDiagnosisSteps($_GET);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Methode nicht erlaubt']);
}

function handleDiagnosis($data) {
    global $pdo;
    
    $session_uuid = $data['session_uuid'] ?? null;
    $step = intval($data['step'] ?? 1);
    $answer = $data['answer'] ?? null;
    
    if (!$session_uuid) {
        $session_uuid = generateUUID();
    }
    
    // Session speichern/aktualisieren
    $stmt = $pdo->prepare("INSERT INTO diagnosis_sessions (session_uuid, current_step, answers) 
                          VALUES (?, ?, ?) 
                          ON CONFLICT (session_uuid) 
                          DO UPDATE SET current_step = EXCLUDED.current_step, 
                                       answers = EXCLUDED.answers, 
                                       updated_at = NOW()");
    $stmt->execute([$session_uuid, $step, json_encode([$step => $answer])]);
    
    // Nächste Frage oder Diagnose
    $nextStep = getNextStep($step, $answer);
    
    if ($nextStep === 'diagnosis') {
        $diagnosis = generateDiagnosis($session_uuid);
        echo json_encode(['diagnosis' => $diagnosis, 'session_uuid' => $session_uuid]);
    } else {
        $question = getQuestion($nextStep);
        echo json_encode(['question' => $question, 'session_uuid' => $session_uuid]);
    }
}

function generateDiagnosis($session_uuid) {
    // Hier würde normalerweise die Claude API aufgerufen
    // Für Demo: Simulierte Antwort
    return [
        'problem' => 'Motorgeräusche',
        'description' => 'Basierend auf Ihren Angaben deutet alles auf ein Problem mit den Ventilen oder dem Zahnriemen hin.',
        'severity' => 'mittel',
        'estimated_cost' => '300-600€',
        'next_steps' => ['Werkstatt aufsuchen', 'Diagnosetest durchführen']
    ];
}

function getQuestion($step) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM diagnosis_questions WHERE step = ?");
    $stmt->execute([$step]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getNextStep($currentStep, $answer) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT next_step_map FROM diagnosis_questions WHERE step = ?");
    $stmt->execute([$currentStep]);
    $map = $stmt->fetchColumn();
    
    if ($map) {
        $map = json_decode($map, true);
        return $map[$answer] ?? 'diagnosis';
    }
    return 'diagnosis';
}

function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}
?>