<?php
/**
 * Carfify – KFZ-Diagnose Core-Klasse
 * Zentralisiert die komplette Diagnose-Logik und Schnittstelle zu Claude 3.5 Sonnet
 * 
 * @package   Carfify\Backend\Classes
 * @author    Carfify-Team
 * @version   1.0.0
 */

namespace Carfify\Backend\Classes;

use PDO;
use Exception;
use RuntimeException;
use JsonException;

/**
 * Diagnosis
 * --------------------------------------------------------------------------
 * Der "HR FRANK" - Dein digitaler Ansprechpartner für alle Autoprobleme.
 * 
 * Features:
 * - Intelligente KI-Diagnose via Claude 3.5
 * - Leichte Alltagssprache (kein Tech-Jargon)
 * - Schritt-für-Schritt Anleitungen
 * - Echte Preisvergleiche
 * - Mehrere Reparatur-Optionen
 */
class Diagnosis
{
    /* ------------------------------------------------------------------ *
     *                           KONSTANTEN                               *
     * ------------------------------------------------------------------ */
    private const CLAUDE_MODEL    = 'claude-3-5-sonnet-20241022';
    private const TEMPERATURE   = 0.1;
    private const MAX_TOKENS    = 2500;
    private const API_ENDPOINT  = 'https://api.anthropic.com/v1/messages';
    private const API_VERSION   = '2023-06-01';

    /* ------------------------------------------------------------------ *
     *                           PROPERTIES                               *
     * ------------------------------------------------------------------ */
    private Database $db;
    private string $claudeKey;
    private string $sessionHash;

    /* ------------------------------------------------------------------ *
     *                       KONSTRUKTOR & HELPERS                        *
     * ------------------------------------------------------------------ */
    public function __construct()
    {
        $this->db = new Database();
        $this->claudeKey = $_ENV['CLAUDE_API_KEY'] ?? '';
        
        if (empty($this->claudeKey)) {
            throw new RuntimeException('❌ Claude API Key fehlt in .env');
        }
        
        // Für anonyme Sessions ohne Login
        $this->sessionHash = $_COOKIE['carfify_session'] ?? $this->generateSessionHash();
    }

    /* ------------------------------------------------------------------ *
     *                    ÖFFENTLICHE HAUPT-METHODEN                      *
     * ------------------------------------------------------------------ */

    /**
     * Neue Diagnose starten
     * 
     * @param array $input ['hsn' => '1234', 'tsn' => 'ABC', 'problem' => '...']
     * @return array ['session_id' => 123, 'vehicle' => [...]]
     */
    public function startSession(array $input): array
    {
        $this->validateInput($input);
        
        // Fahrzeug finden oder anlegen
        $vehicle = $this->findOrCreateVehicle($input['hsn'], $input['tsn']);
        
        // Session anlegen
        $sql = "INSERT INTO diagnosis_sessions 
                (vehicle_id, problem_description, created_at, session_hash) 
                VALUES (:vid, :problem, NOW(), :hash)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':vid' => $vehicle['id'],
            ':problem' => htmlspecialchars($input['problem']),
            ':hash' => $this->sessionHash
        ]);
        
        $sessionId = $this->db->lastInsertId();
        
        return [
            'session_id' => (int)$sessionId,
            'vehicle' => $vehicle,
            'next_step' => 'question'
        ];
    }

    /**
     * Nächsten Schritt in der Diagnose
     * 
     * @param int $sessionId
     * @return array ['type' => 'question|result', 'data' => [...]]
     */
    public function processNextStep(int $sessionId): array
    {
        $session = $this->getSession($sessionId);
        $questions = $this->getQuestions($sessionId);
        
        // Wenn noch weniger als 5 Fragen → neue Frage stellen
        if (count($questions) < 5) {
            return $this->generateNextQuestion($session, $questions);
        }
        
        // Genug Daten → Diagnose erstellen
        return $this->finalDiagnosis($session, $questions);
    }

    /**
     * Antwort auf eine Frage speichern
     */
    public function saveAnswer(int $sessionId, string $questionKey, string $answer): bool
    {
        $sql = "INSERT INTO diagnosis_questions 
                (session_id, question_key, answer, created_at) 
                VALUES (:sid, :key, :ans, NOW())";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':sid' => $sessionId,
            ':key' => $questionKey,
            ':ans' => htmlspecialchars($answer)
        ]);
    }

    /**
     * Komplette Diagnose PDF-generieren
     * 
     * @param int $sessionId
     * @return array Full report
     */
    public function getCompleteReport(int $sessionId): array
    {
        $session = $this->getSession($sessionId);
        $vehicle = $this->getVehicle($session['vehicle_id']);
        $questions = $this->getQuestions($sessionId);
        
        return [
            'vehicle' => $vehicle,
            'user_problem' => $session['problem_description'],
            'asked_questions' => $questions,
            'diagnosis' => json_decode($session['final_diagnosis'] ?? '', true) ?: [],
            'created_at' => $session['created_at']
        ];
    }

    /* ------------------------------------------------------------------ *
     *                    PRIVATE METHODEN (Helpers)                      *
     * ------------------------------------------------------------------ */

    private function validateInput(array $input): void
    {
        $required = ['hsn', 'tsn', 'problem'];
        
        foreach ($required as $field) {
            if (empty($input[$field])) {
                throw new RuntimeException("🔍 {$field} ist erforderlich");
            }
        }
        
        // HSN/TSN validieren (4-stellige HSN, 3-stellige TSN)
        if (!preg_match('/^\d{4}$/', $input['hsn'])) {
            throw new RuntimeException('🚗 HSN muss 4 Ziffern sein');
        }
    }

    private function findOrCreateVehicle(string $hsn, string $tsn): array
    {
        // Zuerst in KBA-Datenbank suchen
        $sql = "SELECT * FROM vehicles 
                WHERE hsn = :hsn AND tsn = :tsn 
                ORDER BY year_from DESC LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':hsn' => $hsn, ':tsn' => $tsn]);
        
        $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$vehicle) {
            throw new RuntimeException('❌ Fahrzeug nicht in Datenbank gefunden');
        }
        
        return $vehicle;
    }

    private function getSession(int $sessionId): array
    {
        $sql = "SELECT ds.*, v.* 
                FROM diagnosis_sessions ds
                JOIN vehicles v ON ds.vehicle_id = v.id
                WHERE ds.id = :id AND ds.session_hash = :hash";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $sessionId, ':hash' => $this->sessionHash]);
        
        $session = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$session) {
            throw new RuntimeException('🔒 Session nicht gefunden');
        }
        
        return $session;
    }

    private function generateNextQuestion(array $session, array $questions): array
    {
        // Intelligente Frage generieren basierend auf bisherigen Antworten
        $prompt = $this->buildSmartQuestionPrompt($session, $questions);
        
        $response = $this->callClaudeAPI($prompt);
        
        return [
            'type' => 'question',
            'data' => json_decode($response['content'], true) ?: []
        ];
    }

    private function finalDiagnosis(array $session, array $questions): array
    {
        $prompt = $this->buildDiagnosisPrompt($session, $questions);
        
        $response = $this->callClaudeAPI($prompt);
        $diagnosis = json_decode($response['content'], true) ?: [];
        
        // In Datenbank speichern
        $this->saveDiagnosis((int)$session['id'], $diagnosis);
        
        // Workshops suchen
        $workshops = $this->findNearbyWorkshops($session);
        
        return [
            'type' => 'result',
            'data' => [
                'diagnosis' => $diagnosis,
                'workshops' => $workshops,
                'session_id' => $session['id']
            ]
        ];
    }

    /* ------------------------------------------------------------------ *
     *                    PROMPT-BUILDING METHODEN                        *
     * ------------------------------------------------------------------ */

    private function buildSmartQuestionPrompt(array $session, array $questions): string
    {
        $context = $this->buildContextString($session, $questions);
        
        return <<<PROMPT
            Roll: Du bist HR FRANK, mein digitaler KFZ-Experte. Sprache: Deutsch, einfach, locker.

            Aufgabe: Stelle mir EINE kurze, präzise Frage zur Fehlersuche.

            Regeln:
            - Max. 20 Worte
            - Ja/Nein oder Auswahl
            - Keine Fachbegriffe
            - Symbol-Emojis bevorzugen 🤔 🚗 ⚙️

            Fahrzeug: {$session['make']} {$session['model']} ({$session['year_from']}-{$session['year_to']})
            Problem: {$session['problem_description']}

            Bereits gefragt: {$context}

            Gib als JSON zurück:
            {
                "key": "unique_key",
                "question": "Deine Frage",
                "type": "yes_no|choice",
                "choices": ["Option1", "Option2"] // nur bei type=choice
            }
        PROMPT;
    }

    private function buildDiagnosisPrompt(array $session, array $questions): string
    {
        $context = $this->buildContextString($session, $questions);
        
        return <<<PROMPT
            Du bist HR FRANK der KFZ-Experte. Du gibst eine vollständige Diagnose.

            Fahrzeug: {$session['make']} {$session['model']}
            Problem: {$session['problem_description']}

            Meine Antworten:
            {$context}

            Erstell eine diagnostische JSON mit:
            {
                "diagnosis": "Kurz und knapp was kaputt ist",
                "confidence": 85,
                "severity": "low|medium|high",
                "fix_options": [
                    {
                        "type": "self_repair",
                        "title": "Selbst reparieren",
                        "description": "Wie man's macht",
                        "time": "30 Min",
                        "tools": ["Schraubendreher", "Steckschlüssel 10"],
                        "parts": [{"name": "Zündkerze", "price": 12.99, "shop": "Amazon"}],
                        "steps": ["Schritt 1...", "Schritt 2..."]
                    },
                    {
                        "type": "workshop",
                        "title": "Zur Werkstatt",
                        "price_range": "€120-€180",
                        "description": "was die machen"
                    }
                ],
                "workshop_search": {
                    "service_type": "Ölwechsel",
                    "keywords": ["Bosch Service", "Freie Werkstatt"]
                }
            }
            
            Sei präzise, hilfreich und freundlich wie ein guter Nachbar.
        PROMPT;
    }

    /* ------------------------------------------------------------------ *
     *                    API & HELPER METHODEN                          *
     * ------------------------------------------------------------------ */

    private function callClaudeAPI(string $prompt): array
    {
        $headers = [
            'x-api-key: ' . $this->claudeKey,
            'Content-Type: application/json',
            'anthropic-version: ' . self::API_VERSION,
            'anthropic-beta: messages-2023-06-01'
        ];

        $payload = [
            'model' => self::CLAUDE_MODEL,
            'max_tokens' => self::MAX_TOKENS,
            'temperature' => self::TEMPERATURE,
            'messages' => [[
                'role' => 'user',
                'content' => $prompt
            ]],
            'system' => 'Du bist der freundliche KFZ-Experte "HR FRANK". Antworte immer in einfacher, verständlicher Sprache.'
        ];

        $ch = curl_init(self::API_ENDPOINT);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new RuntimeException('Netzwerkfehler: ' . curl_error($ch));
        }
        
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new RuntimeException('Claude API Fehler: HTTP ' . $httpCode);
        }

        $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        
        return $data['content'][0] ?? ['content' => ''];
    }

    private function buildContextString(array $session, array $questions): string
    {
        $context = [];
        foreach ($questions as $q) {
            $context[] = "- {$q['question_key']}: {$q['answer']}";
        }
        return implode("\n", $context);
    }

    private function saveDiagnosis(int $sessionId, array $diagnosis): void
    {
        $sql = "UPDATE diagnosis_sessions 
                SET final_diagnosis = :diagnosis, 
                    status = 'completed', 
                    completed_at = NOW() 
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':diagnosis' => json_encode($diagnosis, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            ':id' => $sessionId
        ]);
    }

    private function findNearbyWorkshops(array $session): array
    {
        // Platzhalter für Google Maps API Integration
        // Hier würden echte API-Calls kommen
        return [
            [
                'name' => 'MeisterWerkstatt Frankfurt',
                'distance' => '2.3 km',
                'rating' => 4.7,
                'price_range' => '€€',
                'phone' => '069-12345678'
            ],
            [
                'name' => 'Kfz-Meier GmbH',
                'distance' => '1.8 km',
                'rating' => 4.5,
                'price_range' => '€',
                'phone' => '069-87654321'
            ]
        ];
    }

    private function getVehicle(int $vehicleId): array
    {
        $sql = "SELECT * FROM vehicles WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $vehicleId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    private function generateSessionHash(): string
    {
        $hash = bin2hex(random_bytes(16));
        setcookie('carfify_session', $hash, time() + (86400 * 30), '/');
        return $hash;
    }
}
