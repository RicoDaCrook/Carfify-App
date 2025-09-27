<?php

require_once __DIR__ . '/BaseController.php';

class AiDiagnosisController extends BaseController
{
    public function analyze(): void
    {
        $vehicle = $_SESSION['carfify']['vehicle'] ?? null;
        $analysis = $_SESSION['carfify']['diagnosis'] ?? null;

        $this->render('ai/analysis', [
            'vehicle' => $vehicle,
            'analysis' => $analysis,
            'conversation' => $_SESSION['carfify']['ai_conversation'] ?? [],
            'insights' => $this->buildInsights($vehicle, $analysis),
            'timeline' => $this->buildTimeline($vehicle, $analysis)
        ]);
    }

    public function interactive(): void
    {
        $payload = $this->requireJsonPayload();
        $question = trim($payload['question'] ?? '');
        $answer = trim($payload['answer'] ?? '');

        if ($question === '' || $answer === '') {
            $this->json([
                'error' => 'Bitte übermitteln Sie sowohl Frage als auch Antwort.'
            ], 422);
        }

        $step = [
            'question' => $question,
            'answer' => $answer,
            'timestamp' => time(),
            'follow_up' => $this->generateFollowUp($question, $answer)
        ];

        if (!isset($_SESSION['carfify']) || !is_array($_SESSION['carfify'])) {
            $_SESSION['carfify'] = [];
        }
        if (!isset($_SESSION['carfify']['ai_conversation']) || !is_array($_SESSION['carfify']['ai_conversation'])) {
            $_SESSION['carfify']['ai_conversation'] = [];
        }

        $_SESSION['carfify']['ai_conversation'][] = $step;

        $this->json([
            'status' => 'ok',
            'next' => $step['follow_up'],
            'progress' => $this->progressSummary()
        ]);
    }

    public function progress(): void
    {
        $this->json($this->progressSummary());
    }

    private function buildInsights(?array $vehicle, ?array $analysis): array
    {
        if (!$vehicle || !$analysis) {
            return [
                'message' => 'Bitte wählen Sie zunächst ein Fahrzeug und beschreiben Sie die Symptome, um eine KI-Analyse zu starten.'
            ];
        }

        $focus = $analysis['affectedSystems'] ?? [];
        $severity = $analysis['severity'] ?? 3;

        $probable = [];
        if (in_array('motor', $focus, true)) {
            $probable[] = [
                'title' => 'Zündaussetzer',
                'probability' => min(85, 50 + $severity * 8),
                'description' => 'Analyse der Einspritz- und Zündsysteme aufgrund der gemeldeten Symptome.'
            ];
        }
        if (in_array('elektronik', $focus, true)) {
            $probable[] = [
                'title' => 'Sensorfehler',
                'probability' => 40 + $severity * 6,
                'description' => 'Mehrere Sensorsignale wirken inkonsistent. Steuergeräteabgleich empfohlen.'
            ];
        }
        if (!$probable) {
            $probable[] = [
                'title' => 'Allgemeiner Systemcheck',
                'probability' => 35 + $severity * 5,
                'description' => 'Die Symptome sind breit gestreut. Die KI empfiehlt einen vollständigen Basisscan.'
            ];
        }

        return [
            'vehicleSummary' => sprintf('%s %s (%s)', $vehicle['brand'], $vehicle['model'], $vehicle['engine']),
            'riskLevel' => $analysis['riskLevel'] ?? 'unbekannt',
            'probableCauses' => $probable,
            'nextActions' => [
                'Live-Diagnose mit OBD-Link vorbereiten',
                'Servicehistorie hochladen, um Muster zu erkennen',
                'Werkstattempfehlung anhand Spezialisierung abrufen'
            ],
        ];
    }

    private function buildTimeline(?array $vehicle, ?array $analysis): array
    {
        $timeline = [];
        if ($vehicle) {
            $timeline[] = [
                'title' => 'Fahrzeug ausgewählt',
                'time' => date('d.m.Y H:i', $vehicle['selected_at'] ?? time()),
                'description' => sprintf('%s %s wurde im System verknüpft.', $vehicle['brand'], $vehicle['model'])
            ];
        }
        if ($analysis) {
            $timeline[] = [
                'title' => 'Symptome analysiert',
                'time' => date('d.m.Y H:i', $analysis['created_at']),
                'description' => 'Eingaben wurden bewertet und priorisiert.'
            ];
        }
        foreach ($_SESSION['carfify']['ai_conversation'] ?? [] as $interaction) {
            $timeline[] = [
                'title' => 'KI-Rückfrage beantwortet',
                'time' => date('H:i', $interaction['timestamp']),
                'description' => $interaction['question']
            ];
        }

        return $timeline;
    }

    private function generateFollowUp(string $question, string $answer): string
    {
        $answerLower = mb_strtolower($answer);
        if (strpos($answerLower, 'ja') !== false) {
            return 'Bitte konkretisieren Sie das Geräusch: tritt es dauerhaft oder nur bei bestimmten Drehzahlen auf?';
        }
        if (strpos($answerLower, 'nein') !== false) {
            return 'Gab es kürzlich Wartungen oder Software-Updates, die Einfluss haben könnten?';
        }
        if (strpos($answerLower, 'fehlermeldung') !== false) {
            return 'Bitte fotografieren Sie die Fehlermeldung im Cockpit und laden Sie sie im Kundenbereich hoch.';
        }

        return 'Können Sie das Auftreten zeitlich eingrenzen (z. B. kalt, warm, nach Regen)?';
    }

    private function progressSummary(): array
    {
        $steps = [
            'vehicle' => isset($_SESSION['carfify']['vehicle']),
            'symptoms' => isset($_SESSION['carfify']['diagnosis']),
            'ai' => !empty($_SESSION['carfify']['ai_conversation'] ?? []),
            'pricing' => isset($_SESSION['carfify']['pricing_estimate']),
            'workshop' => isset($_SESSION['carfify']['last_workshop_filter']),
        ];

        $completed = array_sum(array_map(static fn($value) => $value ? 1 : 0, $steps));
        $progress = (int)round(($completed / count($steps)) * 100);

        return [
            'progress' => $progress,
            'steps' => $steps,
        ];
    }
}
