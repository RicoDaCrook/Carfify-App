<?php
/**
 * Kernklasse für die Diagnosefunktionen.
 * Verwaltet Diagnose-Sessions, Fragen und Kostenschätzungen.
 */
require_once __DIR__ . '/Database.php';

class Diagnosis
{
    /** @var PDO Datenbankverbindung */
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Startet eine neue Diagnose-Session.
     *
     * @param int $vehicleId Fahrzeug-ID
     * @param string $symptoms Beschreibung der Symptome
     * @return int ID der neuen Session
     */
    public function startSession(int $vehicleId, string $symptoms): int
    {
        $sql = "INSERT INTO diagnosis_sessions (vehicle_id, symptoms, created_at) VALUES (:vehicle_id, :symptoms, NOW()) RETURNING id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':vehicle_id' => $vehicleId,
            ':symptoms'   => $symptoms,
        ]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Speichert eine Frage mit Antwort in der Session.
     *
     * @param int $sessionId Diagnose-Session-ID
     * @param string $question Gestellte Frage
     * @param string $answer Gegebene Antwort
     * @return void
     */
    public function saveAnswer(int $sessionId, string $question, string $answer): void
    {
        $sql = "INSERT INTO diagnosis_answers (session_id, question, answer, answered_at) VALUES (:session_id, :question, :answer, NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':session_id' => $sessionId,
            ':question'   => $question,
            ':answer'     => $answer,
        ]);
    }

    /**
     * Berechnet eine dynamische Kostenschätzung basierend auf der Session.
     * (vereinfachte Logik: durchschnittlicher Preis pro Symptomtyp)
     *
     * @param int $sessionId Diagnose-Session-ID
     * @return array ['min' => float, 'max' => float]
     */
    public function estimateCost(int $sessionId): array
    {
        // Beispielhafte Logik: Symptome analysieren und Preisspanne ermitteln
        $sql = "SELECT symptoms FROM diagnosis_sessions WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $sessionId]);
        $row = $stmt->fetch();

        if (!$row) {
            return ['min' => 0, 'max' => 0];
        }

        $symptoms = strtolower($row['symptoms']);

        // Vereinfachte Zuordnung von Symptomen zu Preiskategorien
        $costMap = [
            'motor'     => ['min' => 300, 'max' => 1200],
            'getriebe'  => ['min' => 400, 'max' => 1500],
            'bremse'    => ['min' => 150, 'max' => 600],
            'elektrik'  => ['min' => 100, 'max' => 500],
            'default'   => ['min' => 100, 'max' => 800],
        ];

        foreach ($costMap as $keyword => $range) {
            if (strpos($symptoms, $keyword) !== false) {
                return $range;
            }
        }

        return $costMap['default'];
    }

    /**
     * Lädt alle Antworten einer Session.
     *
     * @param int $sessionId Diagnose-Session-ID
     * @return array Liste der Antworten
     */
    public function getAnswers(int $sessionId): array
    {
        $sql = "SELECT question, answer, answered_at FROM diagnosis_answers WHERE session_id = :session_id ORDER BY answered_at";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':session_id' => $sessionId]);
        return $stmt->fetchAll();
    }
}
