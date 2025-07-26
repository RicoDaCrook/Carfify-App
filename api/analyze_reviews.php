<?php
require_once '../config/init.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    analyzeReviews($_POST);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Methode nicht erlaubt']);
}

function analyzeReviews($data) {
    $workshopId = $data['workshop_id'] ?? null;
    
    if (!$workshopId) {
        http_response_code(400);
        echo json_encode(['error' => 'Werkstatt-ID fehlt']);
        return;
    }
    
    // Simulierte KI-Analyse mit Gemini
    // In Produktion würde hier die tatsächliche API aufgerufen
    $analysis = [
        'summary' => 'Die Werkstatt wird für ihre Pünktlichkeit und Kompetenz gelobt. Einige Kunden bemängeln die Preistransparenz.',
        'pros' => ['Fachkompetenz', 'Termintreue', 'Freundlichkeit'],
        'cons' => ['Preise nicht immer transparent', 'lange Wartezeiten'],
        'sentiment' => 'überwiegend positiv'
    ];
    
    echo json_encode($analysis);
}
?>