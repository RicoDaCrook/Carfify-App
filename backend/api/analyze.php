<?php
/* ============================================================
   /backend/api/analyze.php
   Carfify – Vehicle Problem Analyzer (Gemini API Integration)
   Der Endpunkt nimmt ein Diagnose-Ergebnis (summary, symptoms,
   codes) und liefert:
   • eine strukturierte Problem-Beschreibung
   • verständliche Ursachen-Hinweise
   • Risikoeinschätzung (LOW/MEDIUM/HIGH)
   • visuelle Anleitungs-Links (ggf. eigene Guides)
   • geschätzte Reparaturschritte
   • Preisbereich (Parts + Labour)
   ============================================================ */

// Kein Direktzugriff
if (realpath(__FILE__) === realpath($_SERVER['DOCUMENT_ROOT'] . $_SERVER['SCRIPT_NAME'])) {
    http_response_code(404);
    exit('404 Not Found');
}

require_once __DIR__ . '/../security/cors.php';   // CORS-Headers
require_once __DIR__ . '/../config/database.php'; // PostgreSQL-PDO

header('Content-Type: application/json; charset=utf-8');

// === AUTH / VALIDATION ===
$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// JSON einlesen
$rawBody                  = file_get_contents('php://input');
$data                     = json_decode($rawBody, true);

if (!$data || !isset($data['diagnosis_result'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload. Provide "diagnosis_result" object']);
    exit;
}

// ============================================================
//        Funktionen
// ============================================================

/**
 * Aufruf der Gemini-API (Google AI Studio / Vertex)
 * @param string $prompt
 * @return mixed
 */
function callGeminiAPI(string $prompt)
{
    $apiKey       = $_ENV['GOOGLE_API_KEY'] ?? '';      // Environment-Variable
    if (!$apiKey) {
        throw new Exception('Google API-Key is missing');
    }

    $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key={$apiKey}";
    $payload  = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature'      => 0.1,
            'topK'             => 1,
            'maxOutputTokens'  => 1500
        ]
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $endpoint,
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode($payload)
    ]);

    $response   = curl_exec($ch);
    $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err        = curl_error($ch);
    curl_close($ch);

    if ($err || $httpStatus >= 400) {
        throw new Exception('Gemini call failed');
    }

    $json = json_decode($response, true);
    if (!isset($json['candidates'][0]['content']['parts'][0]['text'])) {
        throw new Exception('Unexpected Gemini response structure');
    }

    return $json['candidates'][0]['content']['parts'][0]['text'];
}

// ============================================================
//        LOAD & PARSE INPUT
// ============================================================
$input                    = $data['diagnosis_result'];

$summary = trim($input['summary']         ?? '');
$codes   = $input['dtc_codes']           ?? [];
$symptoms= $input['symptoms']            ?? [];

if (!$summary && empty($codes) && empty($symptoms)) {
    http_response_code(422);
    echo json_encode(['error' => 'Empty input for analysis']);
    exit;
}

// ============================================================
//        GENERATE PROMPT FOR GEMINI
// ============================================================
$prompt = <<<EOT
Du bist ein hilfreicher KI-Assistent für Carfify und spezialisiert auf verständliche Auto-Beschreibungen für „völlige Autolaien“.

Aufgabe: Erstelle eine strukturierte Ausgabe im JSON-Format (keine Erklärung, nur JSON).

Eingabe:
Zusammenfassung (Englisch oder Deutsch): {$summary}
Fehlercodes: " . (empty($codes) ? 'keine' : implode(', ', $codes)) . "
Symptome: " . (empty($symptoms) ? 'keine' : implode(', ', $symptoms)) . "

Gib für alle Feldern deutschsprachige Antworten.

Schema:
{
  "problem_desc": "Kurze klare Beschreibung für Laien",
  "probable_causes": ["Ursache 1", "Ursache 2"],
  "risk_level": "LOW|MEDIUM|HIGH",
  "repair_instructions": ["Stichpunkt 1", "Stichpunkt 2"],
  "estimated_cost_range": { "min_eur": 50, "max_eur": 200, "comment": "günstigste vs Werkstatt" },
  "guide_videos": ["https://carfify.de/guides(:random-id)"],
  "required_tools": ["Schlüssel 13er", "..."]
}
EOT;

try {
    $rawGemini = callGeminiAPI($prompt);
    preg_match('/\{.*\}/s', $rawGemini, $matches);      // JSON extrahieren, falls Markdown vorhanden
    $jsonStr   = $matches[0] ?? '';
    $gemini    = json_decode($jsonStr, true);
} catch (Exception $e) {
    http_response_code(502);
    echo json_encode(['error' => 'Gemini currently unavailable', 'details' => $e->getMessage()]);
    exit;
}

// Fallback-Werte
$analysis = [
    'problem_desc'        => $gemini['problem_desc']       ?? 'Problem konnte nicht identifiziert werden',
    'probable_causes'     => $gemini['probable_causes']    ?? [],
    'risk_level'          => strtoupper($gemini['risk_level'] ?? 'MEDIUM'),
    'repair_instructions' => $gemini['repair_instructions'] ?? [],
    'estimated_cost_range'=> $gemini['estimated_cost_range'] ?? ['min_eur' => 0, 'max_eur' => 0, 'comment'=> 'n/a'],
    'guide_videos'        => $gemini['guide_videos']         ?? [],
    'required_tools'      => $gemini['required_tools']       ?? []
];

// ============================================================
//        SAVE ANALYSIS TO DATABASE (OPTIONAL)
// ============================================================
$sessionId = uniqid('ana_');
$db = new Carfify\Database();  // aus config/database.php
$pdo = $db->getConnection();

$stmt = $pdo->prepare("
  INSERT INTO analysis_results (session_id, raw_input, result_json, created_at)
  VALUES (?, ?, NOW())
");
$stmt->execute([$sessionId, $rawBody, json_encode($analysis)]);

// ============================================================
//        RESPONSE
// ============================================================
echo json_encode([
    'analysis'   => $analysis,
    'session_id' => $sessionId,
    'api_version'=> '1.0'
]);
