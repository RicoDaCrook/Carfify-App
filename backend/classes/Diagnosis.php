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
        
        // Vercel-kompatible Key-Resolving: Variablen in Vercel Dashboard heißen "CLAUDE_API_KEY"
        $this->claudeKey = $_ENV['CLAUDE_API_KEY'] ?? $_SERVER['CLAUDE_API_KEY'] ?? '';
        
        if (empty($this->claudeKey)) {
            error_log('WARNING: Claude API Key missing in environment. Please set CLAUDE_API_KEY in Vercel dashboard.');
            $this->claudeKey = '';
        }
        
        // Session-Hash für Vercel Serverless
        $this->sessionHash = $this->getOrCreateSessionHash();
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
        // Fallback für Serverless-Umgebung wenn API-Key fehlt
        if (empty($this->claudeKey)) {
            return $this->getMockDiagnosisResponse($sessionId);
        }

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
        
        try {
            $response = $this->callClaudeAPI($prompt);
            return [
                'type' => 'question',
                'data' => json_decode($response['content'], true) ?: []
            ];
        } catch (RuntimeException $e) {
            // Fallback bei API-Fehler
            return $this->getMockQuestion();
        }
    }

    private function finalDiagnosis(array $session, array $questions): array
    {
        try {
            $prompt = $this->buildDiagnosisPrompt($session, $questions);
            $response = $this->callClaudeAPI($prompt);
            $diagnosis = json_decode($response['content'], true) ?: [];
            
            // In Datenbank speichern
            $this->saveDiagnosis((int)$session['id'], $diagnosis);
            
            // Workshops suchen
            $workshops = $this->findNearbyWorkshops