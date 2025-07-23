<?php
/**
 * Carfify - Session Handler für Diagnose-Fortschritt
 * 
 * Diese API verwaltet alle Diagnose-Sessions:
 * - Neue Sessions erstellen
 - Fortschritt speichern
 * - Abgebrochene Sessions wiederherstellen
 * - Session-Statistiken
 * 
 * Endpoints:
 * POST /create - Neue Diagnose-Session erstellen
 * PUT /save - Fortschritt speichern
 * GET /load/{id} - Session laden
 * DELETE /clear/{id} - Session löschen
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../security/cors.php';

// CORS-Header setzen
setCorsHeaders();

// Nur JSON-Antworten
header('Content-Type: application/json');

// HTTP-Methode prüfen
$method = $_SERVER['REQUEST_METHOD'];

/**
 * Neue Diagnose-Session initialisieren
 * POST /api/session.php
 * Body: { vehicle_id, problem_description }
 */
if ($method === 'POST' && !isset($_GET['action']) || (isset($_GET['action']) && $_GET['action'] === 'create')) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validierung
        if (!isset($data['vehicle_id']) || !isset($data['problem_description'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Fehlende Parameter']);
            exit;
        }
        
        // Daten bereinigen
        $vehicleId = filter_var($data['vehicle_id'], FILTER_VALIDATE_INT);
        $problemDescription = trim(filter_var($data['problem_description'], FILTER_SANITIZE_STRING));
        
        if (!$vehicleId || empty($problemDescription)) {
            http_response_code(400);
            echo json_encode(['error' => 'Ungültige Parameter']);
            exit;
        }
        
        // Neue Session in Datenbank erstellen
        $conn = Database::getConnection();
        
        $stmt = $conn->prepare("
            INSERT INTO diagnosis_sessions (vehicle_id, problem_description, created_at) 
            VALUES (:vehicle_id, :problem_description, NOW()) 
            RETURNING id
        ");
        
        $stmt->execute([
            'vehicle_id' => $vehicleId,
            'problem_description' => $problemDescription
        ]);
        
        $sessionId = $stmt->fetchColumn();
        
        // Session ID zurückgeben
        echo json_encode([
            'success' => true,
            'session_id' => $sessionId
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Datenbankfehler', 'message' => $e->getMessage()]);
    }
}

/**
 * Fortschritt einer Session speichern
 * PUT /api/session.php?action=save
 * Body: { session_id, question_id, answer, category }
 */
elseif ($method === 'PUT' && isset($_GET['action']) && $_GET['action'] === 'save') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validierung
        if (!isset($data['session_id']) || !isset($data['question']) || !isset($data['answer'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Fehlende Session-Daten']);
            exit;
        }
        
        $sessionId = filter_var($data['session_id'], FILTER_VALIDATE_INT);
        $question = trim(filter_var($data['question'], FILTER_SANITIZE_STRING));
        $answer = trim(filter_var($data['answer'], FILTER_SANITIZE_STRING));
        $category = isset($data['category']) ? trim(filter_var($data['category'], FILTER_SANITIZE_STRING)) : 'general';
        
        // In Datenbank speichern
        $conn = Database::getConnection();
        
        $stmt = $conn->prepare("
            INSERT INTO diagnosis_questions (session_id, question, answer, category) 
            VALUES (:session_id, :question, :answer, :category)
        ");
        
        $stmt->execute([
            'session_id' => $sessionId,
            'question' => $question,
            'answer' => $answer,
            'category' => $category
        ]);
        
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Speicherfehler', 'message' => $e->getMessage()]);
    }
}

/**
 * Komplette Session laden mit allen Antworten
 * GET /api/session.php/[id] oder GET /api/session.php?id=[id]
 */
elseif ($method === 'GET') {
    try {
        // Session-ID aus URL oder Query-Parameter
        $sessionId = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;
        
        if (!$sessionId) {
            http_response_code(400);
            echo json_encode(['error' => 'Session-ID fehlt']);
            exit;
        }
        
        $conn = Database::getConnection();
        
        // Hauptsession laden
        $stmt = $conn->prepare("
            SELECT ds.*, v.make, v.model, v.variant 
            FROM diagnosis_sessions ds 
            JOIN vehicles v ON ds.vehicle_id = v.id 
            WHERE ds.id = :id
        ");
        $stmt->execute(['id' => $sessionId]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$session) {
            http_response_code(404);
            echo json_encode(['error' => 'Session nicht gefunden']);
            exit;
        }
        
        // Alle Fragen laden
        $stmt = $conn->prepare("
            SELECT * FROM diagnosis_questions 
            WHERE session_id = :session_id 
            ORDER BY id ASC
        ");
        $stmt->execute(['session_id' => $sessionId]);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Response zusammenstellen
        echo json_encode([
            'session' => [
                'id' => $session['id'],
                'vehicle' => [
                    'make' => $session['make'],
                    'model' => $session['model'],
                    'variant' => $session['variant']
                ],
                'problem_description' => $session['problem_description'],
                'final_diagnosis' => $session['final_diagnosis'],
                'certainty' => $session['certainty'],
                'created_at' => $session['created_at']
            ],
            'questions' => $questions
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Ladefehler', 'message' => $e->getMessage()]);
    }
}

/**
 * Session als abgeschlossen markieren mit finaler Diagnose
 * POST /api/session.php?action=complete
 * Body: { session_id, final_diagnosis, certainty }
 */
elseif ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'complete') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['session_id']) || !isset($data['final_diagnosis'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Fehlende Diagnose-Daten']);
            exit;
        }
        
        $sessionId = filter_var($data['session_id'], FILTER_VALIDATE_INT);
        $finalDiagnosis = trim(filter_var($data['final_diagnosis'], FILTER_SANITIZE_STRING));
        $certainty = isset($data['certainty']) ? filter_var($data['certainty'], FILTER_VALIDATE_FLOAT) : 0.0;
        
        $conn = Database::getConnection();
        
        $stmt = $conn->prepare("
            UPDATE diagnosis_sessions 
            SET final_diagnosis = :final_diagnosis, 
                certainty = :certainty
            WHERE id = :id
        ");
        
        $stmt->execute([
            'id' => $sessionId,
            'final_diagnosis' => $finalDiagnosis,
            'certainty' => $certainty
        ]);
        
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Update-Fehler', 'message' => $e->getMessage()]);
    }
}

/**
 * Session löschen (für Datenschutz)
 * DELETE /api/session.php/[id]
 */
elseif ($method === 'DELETE') {
    try {
        // Session-ID aus URL extrahieren
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $segments = explode('/', trim($path, '/'));
        $sessionId = filter_var(end($segments), FILTER_VALIDATE_INT);
        
        if (!$sessionId) {
            http_response_code(400);
            echo json_encode(['error' => 'Session-ID fehlt']);
            exit;
        }
        
        $conn = Database::getConnection();
        
        // Transaktion starten
        $conn->beginTransaction();
        
        // Zuerst Fragen löschen (Foreign Key)
        $stmt = $conn->prepare("DELETE FROM diagnosis_questions WHERE session_id = :id");
        $stmt->execute(['id' => $sessionId]);
        
        // Dann Session löschen
        $stmt = $conn->prepare("DELETE FROM diagnosis_sessions WHERE id = :id");
        $stmt->execute(['id' => $sessionId]);
        
        $conn->commit();
        
        echo json_encode(['success' => true, 'deleted' => $sessionId]);
        
    } catch (Exception $e) {
        if (isset($conn)) {
            $conn->rollBack();
        }
        http_response_code(500);
        echo json_encode(['error' => 'Löschfehler', 'message' => $e->getMessage()]);
    }
}

/**
 * Auto-cleanup für alte Sessions (z.B. 30 Tage)
 * GET /api/session.php?action=cleanup
 */
elseif ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'cleanup') {
    try {
        // Admin-Check hinzufügen
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
        if ($apiKey !== $_ENV['CLEANUP_API_KEY']) {
            http_response_code(403);
            echo json_encode(['error' => 'Unbefugt']);
            exit;
        }
        
        $conn = Database::getConnection();
        
        // Alte Sessions löschen
        $stmt = $conn->prepare("
            DELETE FROM diagnosis_sessions 
            WHERE created_at < NOW() - INTERVAL '30 days'
        ");
        
        $deleted = $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'deleted_sessions' => $stmt->rowCount()
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Cleanup-Fehler', 'message' => $e->getMessage()]);
    }
}

// Unbekannte Methoden
else {
    http_response_code(405);
    echo json_encode(['error' => 'Methode nicht erlaubt']);
}
?>
