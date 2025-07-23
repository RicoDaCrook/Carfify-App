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

// Setze Security-Header **vor** jeglicher Output
require_once __DIR__ . '/../security/cors.php';

// Nur POST-Requests zulassen
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['error' => 'Method not allowed']));
}

// Maximale Laufzeit & Memory
set_time_limit(30);
ini_set('memory_limit', '256M');

// JSON-Kontent-Typ
header('Content-Type: application/json; charset=utf-8');

// Anthropic API Credentials – NIEMALS im Repo speichern!
$anthropicKey = $_ENV['ANTHROPIC_API_KEY'] ?? null;
if (!$anthropicKey) {
    http_response_code(500);
    exit(json_encode(['error' => 'API-Schlüssel fehlt']));
}

// ------------------- INPUT VALIDATION SECTION -------------------

// Roh-Body einlesen & dekodieren
$rawBody = file_get_contents('php://input');
$input = json_decode($rawBody, true, 16, JSON_THROW_ON_ERROR);

// Pflichtfelder prüfen (wahlweise mit Vehicle-Id oder textuelle Fahrzeugangaben)
$rules = [
    'description' => 'string|min:5|max:1000',
    'vehicle'     => 'array',      // optional: {'id', 'hsn', 'tsn', 'make', 'model', 'year'}
    'sessionId'   => 'string|uuid|optional',
    'step'        => 'integer|between:1,20',
];

$validated = [];
foreach ($rules as $key => $rule) {
    if (strpos($rule, 'optional') !== false && !isset($input[$key])) {
        continue;
    }
    $check = validateInput($input[$key] ?? null, $rule);
    if ($check !== true) {
        http_response_code(422);
        exit(json_encode(['error' => "Validation failed: {$key} – {$check}"]));
    }
    $validated[$key] = $input[$key];
}

/**
 * Minimale Validierungs-Hilfsfunktion
 */
function validateInput($value, string $rule): true|string
{
    [$type, $param1, $param2] = explode('|', $rule . '||');
    switch ($type) {
        case 'string':
            if (!is_string($value) || strlen($value) < (int)($param1 ?: 0)) {
                return "Must be string, min {$param1} chars";
            }
            break;
        case 'integer':
            if (!is_int($value) || $value < (int)$param1 || $value > (int)($param2)) {
                return "Between {$param1} and {$param2}";
            }
            break;
        case 'array':
            if (!is_array($value)) {
                return 'Array expected';
            }
            break;
        case 'uuid':
            if ($value && !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value)) {
                return 'Invalid UUID';
            }
            break;
    }
    return true;
}

// ------------------- CONTEXT BUILDING SECTION -------------------

// Vehicle-Kontext zusammensuchen
$vehicleContext = '';
if (!empty($validated['vehicle']['id'])) {
    // Falls Vehicle-Id angegeben → aus PostgreSQL laden
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
    $vehicleContext = "Fahrzeug: {$row['make']} {$row['model']} {$row['variant']} (Baujahre {$row['year_from']}-{$row['year_to']})\n";
} elseif (!empty($validated['vehicle'])) {
    // Ansonsten Rohangaben nutzen
    $vehicleContext = "Fahrzeug: {$validated['vehicle']['make']} {$validated['vehicle']['model']} ({$validated['vehicle']['year']})\n";
}

// ------------------- PROMPT-COMPOSITION SECTION -------------------

/**
 * Wir lassen Claude kontextuell & sprachlich „laientauglich“ antworten
 * sowie eine komplette Prüfliste mitgeben
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
  3. Werkstatt-Empfehlung (was wird getan, was kostet’s ca.)
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

// Welche Rolle hat der aktuelle Schritt?
$messages = [];

if ($validated['step'] === 1) {
    // Initiale Nachricht
    $messages[] = [
        'role'    => 'user',
        'content' => $vehicleContext . $validated['description'],
    ];
} else {
    // Rekonstruiere Dialog aus Session
    try {
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
        foreach ($hist as $row) {
            $messages[] = ['role' => 'user',   'content' => $row['question']];
            $messages[] = ['role' => 'assistant', 'content' => $row['answer']];
        }
    } catch (Throwable $e) {
        error_log('Reconstruct session: ' . $e->getMessage());
    }
}

// ------------------- API-CALL TO ANTHROPIC SECTION -------------------

/**
 * Erster API-Call -> erhalten kostenlose Preview-Tokens
 */
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
    CURLOPT_USERAGENT      => 'Carfify/1.0',
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || !$response) {
    http_response_code(502);
    exit(json_encode(['error' => 'KI-API temporär nicht erreichbar']));
}

$claudeResponse = json_decode($response, true, 16, JSON_THROW_ON_ERROR);

$assistantText = $claudeResponse['content'][0]['text'] ?? '';

// ------------------- RESPONSE SANITIZING SECTION -------------------

// Prüfe, ob wir gültiges JSON enthalten
if (!preg_match('/\{(?:[^{}]|(?R))*\}/', $assistantText, $matches)) {
    http_response_code(500);
    exit(json_encode(['error' => 'KI-Antwort entpackbar']));
}
$jsonStr = $matches[0];
$diagnosisJson = json_decode($jsonStr, true, 16, JSON_THROW_ON_ERROR);

// ------------------- SESSION PERSISTANCE SECTION -------------------

if (!empty($validated['sessionId'])) {
    // Antwort speichern
    $stmt = $pdo->prepare("
        INSERT INTO diagnosis_questions (session_id, question, answer, category)
        VALUES (:sid, :q, :a, 'assistant')
    ");
    $stmt->execute([
        ':sid' => $validated['sessionId'],
        ':q'   => 'Anfrage-Schritt ' . $validated['step'],
        ':a'   => $diagnosisJson['summary'] ?? '',
    ]);
}

// ------------------- OUTPUT SECTION -------------------

echo json_encode(
    [
        'step'      => $validated['step'],
        'diagnosis' => $diagnosisJson,
        'debug'     => getenv('DEBUG') ? [
            'request_tokens' => $claudeResponse['usage']['input_tokens'] ?? '?',
            'response_tokens'=> $claudeResponse['usage']['output_tokens'] ?? '?',
        ] : null,
    ],
    JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
);
exit;
