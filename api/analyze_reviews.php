<?php
require_once '../config/init.php';

// POST-Anfrage für Bewertungsanalyse
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Methode nicht erlaubt']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$workshop_id = isset($input['workshop_id']) ? (int)$input['workshop_id'] : null;

if (!$workshop_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Werkstatt-ID fehlt']);
    exit();
}

try {
    // Workshop abrufen
    $stmt = $pdo->prepare("SELECT * FROM workshops WHERE id = ?");
    $stmt->execute([$workshop_id]);
    $workshop = $stmt->fetch();

    if (!$workshop) {
        throw new Exception('Werkstatt nicht gefunden');
    }

    // Simulierte Google Gemini Analyse der Bewertungen
    // In der echten Implementierung würden wir Google Places API nutzen
    $analysis_prompt = "Analysiere folgende Werkstattbewertungen für " . $workshop['name'] . ":\n";
    $analysis_prompt .= "Bewertungen: Durchschnitt " . $workshop['rating'] . "/5 Sterne aus " . $workshop['review_count'] . " Bewertungen\n";
    $analysis_prompt .= "Spezialisierungen: " . implode(', ', $workshop['specialties']) . "\n";
    $analysis_prompt .= "Erstelle eine kurze Zusammenfassung der Stärken und Schwächen basierend auf den Bewertungen.";

    // Simulierte KI-Antwort
    $analysis = [
        'summary' => 'Sehr gute Bewertungen für fachkundige Reparaturen und faire Preise. Kunden loben die schnelle Terminvergabe und transparente Kommunikation.',
        'strengths' => ['Fachkompetenz', 'Transparenz', 'Schnelle Termine'],
        'weaknesses' => ['Parkplätze begrenzt', 'Wartezeit bei Spontanterminen'],
        'overall_score' => 4.5,
        'recommendation' => 'Empfehlenswerte Werkstatt für zuverlässige Reparaturen'
    ];

    echo json_encode([
        'success' => true,
        'analysis' => $analysis,
        'workshop' => $workshop
    ]);

} catch (Exception $e) {
    logError('Bewertungsanalyse Fehler', ['error' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode(['error' => 'Bewertungen konnten nicht analysiert werden']);
}