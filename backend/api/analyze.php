<?php
// backend/api/analyze.php

// CORS-Header für Frontend-Zugriff
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Nur POST erlauben
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// JSON-Body auslesen
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['description'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

$description = trim($input['description']);
if ($description === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Empty description']);
    exit;
}

// Environment-Keys laden
$anthropicKey = $_ENV['ANTHROPIC_API_KEY'] ?? null;
$geminiKey    = $_ENV['GEMINI_API_KEY']    ?? null;

// AI-Client auswählen (bevorzugt Anthropic)
$useAnthropic = $anthropicKey !== null;
$useGemini    = !$useAnthropic && $geminiKey !== null;

if (!$useAnthropic && !$useGemini) {
    http_response_code(500);
    echo json_encode(['error' => 'No AI service configured']);
    exit;
}

// Prompt zusammenstellen
$prompt = <<<EOT
Du bist ein hilfreicher KFZ-Diagnose-Assistent. Analysiere die folgende Fehlerbeschreibung und liefere eine strukturierte Antwort im JSON-Format:

{
  "diagnosis": "Kurze Zusammenfassung des wahrscheinlichen Problems",
  "possible_causes": ["Ursache 1", "Ursache 2", ...],
  "recommended_actions": ["Maßnahme 1", "Maßnahme 2", ...],
  "urgency": "Hoch|Mittel|Niedrig"
}

Beschreibung: "$description"
EOT;

$answer = null;

// 1) Anthropic Claude
if ($useAnthropic) {
    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-api-key: ' . $anthropicKey,
            'anthropic-version: 2023-06-01'
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'model'      => 'claude-3-5-sonnet-20241022',
            'max_tokens' => 512,
            'messages'   => [['role' => 'user', 'content' => $prompt]]
        ])
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $decoded = json_decode($response, true);
    if (isset($decoded['content'][0]['text'])) {
        $answer = $decoded['content'][0]['text'];
    }
}

// 2) Fallback: Google Gemini
if ($answer === null && $useGemini) {
    $ch = curl_init('https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $geminiKey);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode([
            'contents' => [['parts' => [['text' => $prompt]]]]
        ])
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $decoded = json_decode($response, true);
    if (isset($decoded['candidates'][0]['content']['parts'][0]['text'])) {
        $answer = $decoded['candidates'][0]['content']['parts'][0]['text'];
    }
}

// Ergebnis prüfen
if ($answer === null) {
    http_response_code(502);
    echo json_encode(['error' => 'AI service unavailable']);
    exit;
}

// JSON aus AI-Antwort extrahieren
if (preg_match('/\{(?:[^{}]|(?R))*\}/s', $answer, $matches)) {
    $json = json_decode($matches[0], true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo json_encode($json);
        exit;
    }
}

// Fallback: Rohantwort als Diagnose
echo json_encode([
    'diagnosis' => $answer,
    'possible_causes' => [],
    'recommended_actions' => [],
    'urgency' => 'Mittel'
]);
