<?php
/**
 * CARFIFY – Diagnose-API
 * Verarbeitet die interaktive KI-Diagnose mit Claude 3 via Anthropic API
 * Route: POST /api/diagnose.php
 *
 * ACHTUNG: Keine Direktaufrufe erlaubt – nur via valider AJAX-Requests!
 *
 * @author CARFIFY Development
 * @license MIT
 */

declare(strict_types=1);

// ------------------- CONFIG & BOOTSTRAP SECTION -------------------

// Fehlerbehandlung für Produktion
error_reporting(getenv('DEBUG') ? E_ALL : 0);
ini_set('display_errors', 0);

// Setze Security-Header vor jeglicher Output
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

require_once __DIR__ . '/../security/cors.php';

// Nur POST-Requests zulassen
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['error' => 'Method not allowed']));
}

// Maximale Laufzeit & Memory (Vercel-spezifische Limits beachten)
set_time_limit(30);
ini_set('memory_limit', '256M');

// JSON-Content-Type
header('Content-Type: application/json; charset=utf-8');

// Anthropic API Credentials - Environment Variables nutzen
$anthropicKey = $_ENV['ANTHROPIC_API_KEY'] ?? false;
if (!$anthropicKey) {
    http_response_code(500);
    exit(json_encode(['error' => 'API configuration error']));
}

// ------------------- INPUT VALIDATION SECTION -------------------

// Fehlerhafte JSON-Requests abfangen
try {
    $rawBody = file_get_contents('php://input');
    if (empty($rawBody)) {
        throw new Exception('Empty request body');
    }
    $input = json_decode($rawBody, true, 16, JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid JSON format']));
}

// Validate required fields
$rules = [
    'description' => ['type' => 'string', 'min' => 5, 'max' => 1000, 'required' => true],
    'vehicle'     => ['type' => 'array', 'required' => false],
    'sessionId'   => ['type' => 'uuid', 'required' => false],
    'step'        => ['type' => 'integer', 'min' => 1, 'max' => 20, 'required' => true],
];

$validated = [];
foreach ($rules as $key => $rule) {
    if ($rule['required'] === false && !isset($input[$key])) {
        continue;
    }
    
    if (!isset($input[$key])) {
        http_response_code(422);
        exit(json_encode(['error' => "Missing required field: {$key}"]));
    }
    
    $check = validateInput($input[$key], $rule);
    if ($check !== true) {
        http_response_code(422);
        exit(json_encode(['error' => "Validation failed: {$key} – {$check}"]));
    }
    $validated[$key] = $input[$key];
}

/**
 * Verbesserte Validierungsfunktion
 */
function validateInput($value, array $rule): true|string
{
    switch ($rule['type']) {
        case 'string':
            if (!is_string($value) || strlen($value) < $rule['min'] || strlen($value) > $rule['max']) {
                return "Must be string, between {$rule['min']} and {$rule['max']} chars";
            }
            break;
        case 'integer':
            if (!is_int($value) || $value < $rule['min'] || $value > $rule['max']) {
                return "Must be integer between {$rule['min']} and {$rule['max']}";
            }
            break;
        case 'array':
            if (!is_array($value)) {
                return 'Array expected';
            }
            break;
        case 'uuid':
            if ($value && !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value)) {
                return 'Invalid UUID format';
            }
            break;
    }
    return true;
}

// ------------------- CONTEXT BUILDING SECTION -------------------

// Vehicle-Kontext zusammenstellen
$vehicleContext = '';
if (!empty($validated['vehicle']['id'])) {
    try {
        require_once __DIR__ . '/../classes/Database.php';
        $pdo = (new \Carfify\Classes\Database())->getPDO();
        $stmt = $pdo->prepare("
            SELECT make, model, variant, year_from, year_to
            FROM vehicles
            WHERE id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $validated['vehicle']['id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $vehicleContext = "Fahrzeug: {$row['make']} {$row['model']} {$row['variant']} (Baujahre {$row['year_from']}-{$row['year_to']})\n";
        }
    } catch (Throwable $e) {
        // Fahrzeugdaten nicht kritisch - Fehler loggen aber durchlaufen
        error_log('Vehicle lookup failed: ' . $e->getMessage());
    }
} elseif (!empty($validated['vehicle']) && is_array($validated['vehicle'])) {
    $make = $validated['vehicle']['make'] ?? 'Unknown';
    $model = $validated['vehicle']['model'] ?? 'Unknown';
    $year = $validated['vehicle']['year'] ?? 'Unknown';
    $vehicleContext = "Fahrzeug: {$make} {$model} ({$year})\n";
}

// ------------------- PROMPT-COMPOSITION SECTION -------------------

/**
 * System-Prompt für KI-Diagnose
 */
$systemPrompt = <<<PROMPT
Du bist ein freundlicher und sanfter KFZ-Diagnose-Assistent für absolute Anfänger.
Sprache: Deutsch, einfach, umgangssprachlich, stets ermutigend (niemals wertend).

Aufgabe:
- Erkenne Fahrzeugprobleme aus Nutzerbeschreibung
- Stelle maximal 3 sehr einfache Ja/Nein- oder Auswahl-Fragen
- Am Ende gib eine möglichst konkrete Diagnose mit Sicherheits-Score (0-100 %)
- Liefere 3 Lösungswege:
  1. Selbstreparatur (inkl. 3-schrittige Prüfliste mit Sicherheitshinweisen)
  2. Hybrid (welches Teil + ungefähre Kosten + Montage-Beschreibung)
  3. Werkstatt-Empfehlung (was wird getan, was kostet's ca.)
- Nutze Smileys und Bullet-Points, um Lesefreundlichkeit zu erhöhen

Antwort-Format (JSON):
{
  "questions": [ "Frage 1?", "Frage 2?", "Frage 3?" ],
  "summary": "Kurze Diagnose-Beschreibung (max 250 Zeichen)",
  "diagnosis": {
    "issue": "Geräusch beim Bremsen",
    "certainty": 85,
    "recommended_parts": [ "Bremsklötze vorn", "Bremsenreiniger" ],
    "estimated_cost_self": 40,
    "estimated_cost_workshop": 230,
    "safety_notice": "Keine Reparatur ohne Achsständer!"
  },
  "guides": [
    "1. Radmuttern mit Kreuzschlüssel lose machen (Auto bremst)",
    "2. Wagen aufbocken & Achsständer positionieren",
    "3. ..."
  ]
}
Achte stets auf Datenschutz und legal korrekte Angaben.
PROMPT;

$messages = [];

if ($validated['step'] === 1) {
    // Initiale Nachricht
    $messages[] = [
        'role'    => 'user',
        'content' => $vehicleContext . trim($validated['description']),
    ];
} else {
    // Rekonstruiere Dialog aus Session
    try {
        if (empty($validated['sessionId'])) {
            throw new Exception('No session ID for step > 1');
        }
        
        $pdo = (new \Carfify\Classes\Database())->getPDO();
        $stmt = $pdo->prepare("
            SELECT q.question, q.answer
            FROM diagnosis_questions q
            JOIN diagnosis_sessions s ON s.id = q.session_id
            WHERE s.id = :sid
            ORDER BY q.id ASC
        ");
        $stmt->execute([':sid' => $validated['sessionId']]);
        $hist = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!$hist) {
            // Session nicht gefunden → neu starten
            $messages[] = [
                'role'    => 'user',
                'content' => $vehicleContext . trim($validated['description']),
            ];
        } else {
            foreach ($hist as $row) {
                $messages[] = ['role' => 'user', 'content' => $row['question']];
                $messages[] = ['role' => 'assistant', 'content' => $row['answer']];
            }
        }
    } catch (Throwable $e) {
        // Bei Session-Fehlern neu starten
        error_log('Session reconstruction failed: ' . $e->getMessage());
        $messages[] = [
            'role'    => 'user',
            'content' => $vehicleContext . trim($validated['description']),
        ];
    }
}

// ------------------- API-CALL TO ANTHROPIC SECTION -------------------

try {
    $payload = [
        'model'      => 'claude-3-haiku-20240307',
        'max_tokens' => 800,
        'system'     => $systemPrompt,
        'messages'   => $messages,
        'temperature'=> 0.7,
    ];

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'x-api-key: ' . $anthropicKey,
            'anthropic-version: 2023-06-01',
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_USERAGENT      => 'Carfify/1.0-php',
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        throw new Exception('CURL error: ' . $curlError);
    }
    
    if ($httpCode !== 200 || !$response) {
        throw new Exception("API error: HTTP {$httpCode}");
    }

    $claudeResponse = json_decode($response, true, 16, JSON_THROW_ON_ERROR);

} catch (Throwable $e) {
    http_response_code(502);
    exit(json_encode(['error' => 'KI-API temporär nicht erreichbar']));
}

$assistantText = $claudeResponse['content'][0]['text'] ?? '';

// ------------------- RESPONSE SANITIZING SECTION -------------------

// Extrahiere JSON aus KI-Antwort
if (!preg_match('/\{(?:[^{}]|(?R))*\}/', $assistantText, $matches)) {
    http_response_code(500);
    exit(json_encode(['error' => 'KI-Antwortformat ungültig']));
}

try {
    $diagnosisJson = json_decode($matches[0], true, 16, JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    http_response_code(500);
    exit(json_encode(['error' => 'KI-Antwort enthält kein gültiges JSON']));
}

// ------------------- SESSION PERSISTANCE SECTION -------------------

try {
    if (!empty($validated['sessionId']) && isset($diagnosisJson['summary'])) {
        $stmt = $pdo->prepare("
            INSERT INTO diagnosis_questions (session_id, question, answer, category)
            VALUES (:sid, :q, :a, 'assistant')
        ");
        $stmt->execute([
            ':sid' => $validated['sessionId'],
            ':q'   => 'Anfrage-Schritt ' . $validated['step'],
            ':a'   => $diagnosisJson['summary'],
        ]);
    }
} catch (Throwable $e) {
    // Session-Persistenz ist nicht kritisch
    error_log('Session persistence failed: ' . $e->getMessage());
}

// ------------------- OUTPUT SECTION -------------------

$debug = [];
if (getenv('DEBUG') === 'true') {
    $debug = [
        'request_tokens'  => $claudeResponse['usage']['input_tokens'] ?? 0,
        'response_tokens' => $claudeResponse['usage']['output_tokens'] ?? 0,
        'step'            => $validated['step'],
    ];
}

header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

echo json_encode(
    [
        'step'      => $validated['step'],
        'diagnosis' => $diagnosisJson,
        'debug'     => $debug,
    ],
    JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
);
exit;
