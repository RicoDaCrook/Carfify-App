<?php
/* ============================================================
   /backend/api/analyze.php
   Carfify – Vehicle Problem Analyzer (Gemini API Integration)
   Vercel-Deployment Ready Edition
   ============================================================ */

// Kein Direktzugriff - Vercel-kompatibel
if (PHP_SAPI === 'cli-server' && basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    http_response_code(404);
    exit('404 Not Found');
}

require_once __DIR__ . '/../security/cors.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

// === ERROR HANDLING FÜR VERCEL ===
set_error_handler(function($errno, $errstr) {
    throw new ErrorException($errstr, 500);
});

// === AUTH / VALIDATION ===
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// JSON einlesen
$rawBody = file_get_contents('php://input');
if (!$rawBody) {
    http_response_code(400);
    echo json_encode(['error' => 'Empty request body']);
    exit;
}

$data = json_decode($rawBody, true);
if (!$data || !isset($data['diagnosis_result']) || !is_array($data['diagnosis_result'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON payload. Provide "diagnosis_result" as object']);
    exit;
}

// ============================================================
//        Funktionen
// ============================================================

/**
 * Vercel-kompatible Gemini API Call
 */
function callGeminiAPI(string $prompt): string
{
    $apiKey = $_ENV['GOOGLE_API_KEY'] ?? false;
    
    // Fallback auf Vercel Environment Variables
    if (!$apiKey && function_exists('vercel_env')) {
        $apiKey = vercel_env('GOOGLE_API_KEY');
    }
    
    // Final check
    if (!$apiKey) {
        throw new Exception('GOOGLE_API_KEY is not configured');
    }

    $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key={$apiKey}";
    
    $payload = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.1,
            'topK' => 1,
            'maxOutputTokens' => 1500
        ]
    ];

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => [
                'Content-Type: application/json',
                'User-Agent: Carfify/1.0 (Vercel-PHP)'
            ],
            'content' => json_encode($payload),
            'timeout' => 15
        ]
    ]);

    $response = @file_get_contents($endpoint, false, $context);
    if ($response === false) {
        throw new Exception('Gemini API call failed');
    }

    return $response;
}

// ============================================================
//        LOAD & PARSE INPUT
// ============================================================
$input = $data['diagnosis_result'];

$summary = trim($input['summary'] ?? '');
$codes = array_filter(array_map('trim', $input['dtc_codes'] ?? []));
$symptoms = array_filter(array_map('trim', $input['symptoms'] ?? []));

if (!$summary && empty($codes) && empty($symptoms)) {
    http_response_code(422);
    echo json_encode(['error' => 'Empty input for analysis']);
    exit;
}

// ============================================================
//        GENERATE PROMPT FOR GEMINI
// ============================================================
$prompt = <<<EOT
You are an AI assistant for Carfify, specializing in clear vehicle problem descriptions for absolute car laymen.

Task: Create structured JSON output in German language ONLY.

Input:
Summary: {$summary}
DTC Codes: " . (empty($codes) ? 'none' : implode(', ', $codes)) . "
Symptoms: " . (empty($symptoms) ? 'none' : implode(', ', $symptoms)) . "

Return ONLY this exact JSON structure with German values:

{
  "problem_desc": "Clear problem description for laymen, max 150 chars",
  "probable_causes": ["Cause 1", "Cause 2", "Cause 3"],
  "risk_level": "LOW|MEDIUM|HIGH",
  "repair_instructions": ["Step 1", "Step 2", "Step 3"],
  "estimated_cost_range": {"min_eur": 50, "max_eur": 500, "comment": "DIY vs workshop"},
  "guide_videos": ["https://carfify.de/guide/random-id"],
  "required_tools": ["13er Steckschlüssel", "Schraubendreher", "Wagenheber"]
}
EOT;

// ============================================================
//        GEMINI API CALL
// ============================================================
try {
    $geminiResponse = callGeminiAPI($prompt);
    $jsonData = json_decode($geminiResponse, true);
    
    if (!is_array($jsonData) || !isset($jsonData['candidates'][0]['content']['parts'][0]['text'])) {
        throw new Exception('Invalid Gemini response structure');
    }
    
    $rawGemini = $jsonData['candidates'][0]['content']['parts'][0]['text'];
    
    // JSON extrahieren
    preg_match('/\{.*\}/s', $rawGemini, $matches);
    if (!$matches) {
        throw new Exception('No JSON found in Gemini response');
    }
    
    $analysisData = json_decode($matches[0], true);
    if (!is_array($analysisData)) {
        throw new Exception('Invalid JSON structure from Gemini');
    }
    
} catch (Exception $e) {
    http_response_code(503);
    echo json_encode([
        'error' => 'Service temporarily unavailable',
        'message' => 'AI analysis failed',
        'timestamp' => date('c')
    ]);
    exit;
}

// ============================================================
//        DATABASE HANDLING (OPTIONAL - FÜR VERCEL)
// ============================================================
// Unter Vercel: Für echte Persistenz PostgreSQL, MongoDB oder Supabase nutzen
if (isset($_ENV['DATABASE_URL']) && !empty($_ENV['DATABASE_URL'])) {
    try {
        $sessionId = uniqid('carfify_');
        $db = new Carfify\Database();
        $pdo = $db->getConnection();
        
        $stmt = $pdo->prepare("
            INSERT INTO analysis_results (session_id, raw_input, result_json, created_at) 
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$sessionId, $rawBody, json_encode($analysisData)]);
    } catch (Exception $e) {
        // Silent fail - nicht kritisch für die API
    }
}

// ============================================================
//        RESPONSE
// ============================================================
$response = [
    'analysis' => $analysisData,
    'session_id' => $sessionId ?? uniqid('carfify_'),
    'api_version' => '1.0-vercel',
    'timestamp' => date('c')
];

// Pretty print für Entwicklung
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
