<?php

require_once __DIR__ . '/BaseController.php';

class DiagnosisController extends BaseController
{
    private array $questionCatalog;

    public function __construct()
    {
        $this->questionCatalog = $this->loadQuestionCatalog();
    }

    public function problemForm(): void
    {
        $this->render('diagnosis/problem-form', [
            'vehicle' => $_SESSION['carfify']['vehicle'] ?? null,
            'questions' => $this->questionCatalog,
            'errors' => [],
            'old' => []
        ]);
    }

    public function processProblem(): void
    {
        $symptoms = trim($_POST['symptoms'] ?? '');
        $severity = (int)($_POST['severity'] ?? 3);
        $mileage = trim($_POST['mileage'] ?? '');
        $usage = trim($_POST['usage_pattern'] ?? '');
        $recentChanges = array_filter(array_map('trim', $_POST['recent_changes'] ?? []));
        $environment = trim($_POST['environment'] ?? '');

        $errors = [];
        if ($symptoms === '') {
            $errors['symptoms'] = 'Bitte beschreiben Sie die auffälligen Symptome so konkret wie möglich.';
        }
        if ($mileage !== '' && !is_numeric($mileage)) {
            $errors['mileage'] = 'Die Laufleistung muss eine Zahl sein.';
        }
        if ($severity < 1 || $severity > 5) {
            $errors['severity'] = 'Die Priorität muss zwischen 1 und 5 liegen.';
        }

        if ($errors) {
            http_response_code(422);
            $this->render('diagnosis/problem-form', [
                'vehicle' => $_SESSION['carfify']['vehicle'] ?? null,
                'questions' => $this->questionCatalog,
                'errors' => $errors,
                'old' => [
                    'symptoms' => $symptoms,
                    'severity' => $severity,
                    'mileage' => $mileage,
                    'usage_pattern' => $usage,
                    'recent_changes' => $recentChanges,
                    'environment' => $environment,
                ]
            ]);
            return;
        }

        $detectedSystems = $this->detectAffectedSystems($symptoms, $recentChanges);
        $riskLevel = $this->calculateRiskLevel($severity, $detectedSystems);
        $timeCritical = $this->isTimeCritical($severity, $detectedSystems);

        $analysis = [
            'symptoms' => $symptoms,
            'severity' => $severity,
            'mileage' => $mileage === '' ? null : (int)$mileage,
            'usage' => $usage ?: null,
            'recentChanges' => $recentChanges,
            'environment' => $environment ?: null,
            'affectedSystems' => $detectedSystems,
            'riskLevel' => $riskLevel,
            'timeCritical' => $timeCritical,
            'created_at' => time(),
        ];

        if (!isset($_SESSION['carfify']) || !is_array($_SESSION['carfify'])) {
            $_SESSION['carfify'] = [];
        }

        $_SESSION['carfify']['diagnosis'] = $analysis;

        $this->render('diagnosis/result', [
            'vehicle' => $_SESSION['carfify']['vehicle'] ?? null,
            'analysis' => $analysis,
            'questionCatalog' => $this->questionCatalog,
            'nextQuestions' => $this->compileNextQuestions($detectedSystems),
            'recommendations' => $this->buildRecommendations($analysis)
        ]);
    }

    private function loadQuestionCatalog(): array
    {
        $path = __DIR__ . '/../storage/data/diagnosis_questions.php';
        if (!file_exists($path)) {
            throw new RuntimeException('Der Fragenkatalog konnte nicht geladen werden.');
        }

        $data = require $path;
        if (!is_array($data)) {
            throw new RuntimeException('Der Fragenkatalog ist beschädigt.');
        }

        return $data;
    }

    private function detectAffectedSystems(string $symptoms, array $recentChanges): array
    {
        $symptomText = mb_strtolower($symptoms);
        $systems = [
            'motor' => ['motor', 'start', 'leistung', 'ruckeln', 'zünd', 'öl', 'klopf'],
            'antrieb' => ['getriebe', 'schalten', 'kupplung', 'antrieb', 'dsg'],
            'bremsen' => ['bremse', 'abs', 'quietsch', 'pedal', 'bremsweg'],
            'fahrwerk' => ['fahrwerk', 'lenkung', 'spur', 'vibration', 'stossdämpfer'],
            'elektronik' => ['elektr', 'sensor', 'steuergerät', 'batterie', 'steuerung'],
            'klima' => ['klima', 'ac', 'luft', 'heizung'],
        ];

        $detected = [];
        foreach ($systems as $system => $keywords) {
            foreach ($keywords as $keyword) {
                if (mb_strpos($symptomText, $keyword) !== false) {
                    $detected[$system] = ($detected[$system] ?? 0) + 1;
                }
            }
        }

        foreach ($recentChanges as $change) {
            $lower = mb_strtolower($change);
            if (strpos($lower, 'inspektion') !== false) {
                $detected['service'] = ($detected['service'] ?? 0) + 1;
            }
            if (strpos($lower, 'software') !== false) {
                $detected['elektronik'] = ($detected['elektronik'] ?? 0) + 1;
            }
        }

        arsort($detected);
        return array_keys($detected);
    }

    private function calculateRiskLevel(int $severity, array $detectedSystems): string
    {
        if ($severity >= 5) {
            return 'kritisch';
        }
        if ($severity === 4) {
            return in_array('motor', $detectedSystems, true) || in_array('bremsen', $detectedSystems, true)
                ? 'hoch'
                : 'erhöht';
        }
        if ($severity === 3) {
            return in_array('motor', $detectedSystems, true) ? 'erhöht' : 'mittel';
        }
        return $severity <= 2 ? 'niedrig' : 'mittel';
    }

    private function isTimeCritical(int $severity, array $detectedSystems): bool
    {
        if ($severity >= 4) {
            return true;
        }
        return (bool)array_intersect($detectedSystems, ['bremsen', 'antrieb']);
    }

    private function compileNextQuestions(array $detectedSystems): array
    {
        if (!$detectedSystems) {
            return $this->questionCatalog['allgemein']['questions'];
        }

        $selected = [];
        foreach ($detectedSystems as $system) {
            if (isset($this->questionCatalog[$system]['questions'])) {
                $selected = array_merge($selected, $this->questionCatalog[$system]['questions']);
            }
        }

        return $selected ?: $this->questionCatalog['allgemein']['questions'];
    }

    private function buildRecommendations(array $analysis): array
    {
        $recommendations = [];
        $severity = $analysis['severity'];
        $systems = $analysis['affectedSystems'];

        if (in_array('motor', $systems, true)) {
            $recommendations[] = 'Motor-Kompressionstest sowie Prüfung der Einspritzwerte durchführen lassen.';
        }
        if (in_array('bremsen', $systems, true)) {
            $recommendations[] = 'Sichtprüfung der Bremsanlage und Messung der Bremsflüssigkeit vornehmen.';
        }
        if (in_array('elektronik', $systems, true)) {
            $recommendations[] = 'OBD-Diagnose und Software-Update auf ausstehende Fehlercodes prüfen.';
        }
        if (!$recommendations) {
            $recommendations[] = 'Geführten Schnelltest über die KI durchführen, um weitere Eingrenzungen zu erhalten.';
        }

        if ($severity >= 4) {
            $recommendations[] = 'Fahrzeug bis zur finalen Diagnose nur eingeschränkt nutzen.';
        }

        return $recommendations;
    }

    public function processProblemViaApi(string $symptoms, int $severity, array $payload): array
    {
        $recentChanges = array_filter(array_map('trim', $payload['recent_changes'] ?? []));
        $detectedSystems = $this->detectAffectedSystems($symptoms, $recentChanges);

        return [
            'affected_systems' => $detectedSystems,
            'risk_level' => $this->calculateRiskLevel($severity, $detectedSystems),
            'time_critical' => $this->isTimeCritical($severity, $detectedSystems),
            'recommendations' => $this->buildRecommendations([
                'severity' => $severity,
                'affectedSystems' => $detectedSystems,
            ]),
        ];
    }
}
