<?php
/**
 * diagnose.php
 * Endpunkt zur Durchführung einer KI-Diagnose.
 * Erwartet JSON-Body mit vehicle_id, problem_description und optionalen user_answers.
 * Gibt Diagnoseergebnis (JSON) zurück.
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

$vehicle_id = $input['vehicle_id'] ?? null;
$problem_description = $input['problem_description'] ?? '';
$user_answers = $input['user_answers'] ?? [];

if (!$vehicle_id || trim($problem_description) === '') {
    http_response_code(400);
    echo json_encode(['error' => 'vehicle_id und problem_description erforderlich.']);
    exit();
}

// Datenbankverbindung
require_once __DIR__ . '/../config/database.php';

// Fahrzeug abrufen
$pdo = new PDO(DB_DSN, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = :id");
$stmt->execute([':id' => $vehicle_id]);
$vehicle = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$vehicle) {
    http_response_code(404);
    echo json_encode(['error' => 'Fahrzeug nicht gefunden.']);
    exit();
}

// Simulierter Aufruf der Claude API
// In Produktion: HTTP-Request an Anthropic Claude API
$diagnosis = simulateClaudeDiagnosis($vehicle, $problem_description, $user_answers);

// Diagnose speichern (optional)
$stmt = $pdo->prepare(
    "INSERT INTO diagnoses (vehicle_id, problem_description, diagnosis_json, created_at)
     VALUES (:vid, :prob, :diag, NOW())"
);
$stmt->execute([
    ':vid' => $vehicle_id,
    ':prob' => $problem_description,
    ':diag' => json_encode($diagnosis)
]);

echo json_encode($diagnosis);

/**
 * Simuliert den Aufruf der Claude API.
 * @param array $vehicle
 * @param string $problem
 * @param array $answers
 * @return array
 */
function simulateClaudeDiagnosis(array $vehicle, string $problem, array $answers): array
{
    // Vereinfachte Logik zur Simulation
    $possibleIssues = [
        'Bremsen' => ['Bremsbeläge verschlissen', 'Bremsflüssigkeit niedrig'],
        'Motor' => ['Zündkerzen defekt', 'Ölstand zu niedrig'],
        'Elektrik' => ['Batterie schwach', 'Sicherung durchgebrannt'],
        'Getriebe' => ['Getriebeöl niedrig', 'Kupplung verschleißt'],
    ];

    // Dummy-Kategorie auswählen
    $category = array_keys($possibleIssues)[rand(0, count($possibleIssues) - 1)];
    $issue = $possibleIssues[$category][rand(0, count($possibleIssues[$category]) - 1)];

    return [
        'category' => $category,
        'issue' => $issue,
        'severity' => rand(1, 5),
        'estimated_cost_min' => rand(50, 200),
        'estimated_cost_max' => rand(200, 800),
        'next_questions' => [], // Optional: weitere Rückfragen
        'workshop_category' => strtolower($category)
    ];
}