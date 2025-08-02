<?php
require_once 'base.php';
require_once '../classes/MeisterMueller.php';

header('Content-Type: application/json');

class DiagnoseAPI extends BaseAPI {
    private $meister;
    
    public function __construct() {
        parent::__construct();
        $this->meister = new MeisterMueller();
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? '';
        
        switch($method) {
            case 'GET':
                switch($action) {
                    case 'quick-questions':
                        $this->getQuickQuestions();
                        break;
                    case 'checklist':
                        $this->getChecklist($_GET['symptom'] ?? '');
                        break;
                    case 'safety-score':
                        $this->calculateSafetyScore($_GET['answers'] ?? []);
                        break;
                    default:
                        $this->error('Ungültige Aktion');
                }
                break;
                
            case 'POST':
                switch($action) {
                    case 'chat':
                        $this->handleChat($_POST['message'] ?? '', $_POST['context'] ?? []);
                        break;
                    case 'analyze-symptom':
                        $this->analyzeSymptom($_POST);
                        break;
                    default:
                        $this->error('Ungültige Aktion');
                }
                break;
        }
    }
    
    private function getQuickQuestions() {
        $questions = [
            [
                'id' => 'engine_noise',
                'question' => 'Hören Sie ungewöhnliche Geräusche aus dem Motor?',
                'description' => 'Klingt wie ein ratterndes Geräusch?'
            ],
            [
                'id' => 'brake_squeal',
                'question' => 'Quietschen Ihre Bremsen beim Bremsen?',
                'description' => 'Hören Sie ein piepsendes Geräusch?'
            ],
            [
                'id' => 'oil_light',
                'question' => 'Leuchtet die Öl-Kontrollleuchte?',
                'description' => 'Rotes Öl-Kännchen im Display?'
            ],
            [
                'id' => 'coolant_temp',
                'question' => 'Steigt die Temperatur über die Mitte?',
                'description' => 'Zeigt der Temperaturmesser zu heiß an?'
            ],
            [
                'id' => 'steering_vibration',
                'question' => 'Vibriert das Lenkrad beim Fahren?',
                'description' => 'Spüren Sie ein Kribbeln in den Händen?'
            ]
        ];
        
        $this->success(['questions' => $questions]);
    }
    
    private function getChecklist($symptom) {
        $checklists = [
            'engine_noise' => [
                [
                    'id' => 'check_oil_level',
                    'title' => 'Ölstand prüfen',
                    'instruction' => 'Ziehen Sie den gelben Öl-Messstab heraus (links am Motor), wischen Sie ihn ab, stecken Sie ihn wieder rein und ziehen Sie ihn erneut raus. Der Ölstand sollte zwischen Min und Max sein.',
                    'difficulty' => 'leicht',
                    'time' => '2 Minuten'
                ],
                [
                    'id' => 'check_belts',
                    'title' => 'Keilriemen anschauen',
                    'instruction' => 'Öffnen Sie die Motorhaube (Hebel links unter dem Lenkrad). Schauen Sie auf die Riemen - dürfen nicht gerissen oder locker sein.',
                    'difficulty' => 'leicht',
                    'time' => '3 Minuten'
                ]
            ],
            'brake_squeal' => [
                [
                    'id' => 'check_brake_fluid',
                    'title' => 'Bremsflüssigkeit prüfen',
                    'instruction' => 'Im Motorraum finden Sie einen Behälter mit "BRAKE" beschriftet. Die Flüssigkeit sollte zwischen Min und Max sein.',
                    'difficulty' => 'leicht',
                    'time' => '2 Minuten'
                ],
                [
                    'id' => 'visual_brake_check',
                    'title' => 'Bremsen durch Felgen schauen',
                    'instruction' => 'Schauen Sie durch die Speichen Ihrer Felgen. Die Bremsscheiben sollten glatt sein, die Beläge mindestens 3mm dick.',
                    'difficulty' => 'mittel',
                    'time' => '5 Minuten'
                ]
            ]
        ];
        
        $this->success(['checklist' => $checklists[$symptom] ?? []]);
    }
    
    private function calculateSafetyScore($answers) {
        $baseScore = 100;
        
        // Reduziere Punkte für kritische Probleme
        $penalties = [
            'engine_noise' => 25,
            'oil_light' => 30,
            'coolant_temp' => 35,
            'brake_squeal' => 20,
            'steering_vibration' => 15
        ];
        
        foreach ($answers as $questionId => $answer) {
            if ($answer === 'yes' && isset($penalties[$questionId])) {
                $baseScore -= $penalties[$questionId];
            }
        }
        
        $score = max(40, min(100, $baseScore));
        
        $this->success(['safety_score' => $score]);
    }
    
    private function handleChat($message, $context) {
        if (empty($message)) {
            $this->error('Keine Nachricht angegeben');
            return;
        }
        
        $response = $this->meister->generateResponse($message, $context);
        
        $this->success(['response' => $response]);
    }
    
    private function analyzeSymptom($data) {
        $symptom = $data['symptom'] ?? '';
        $hsn = $data['hsn'] ?? '';
        $tsn = $data['tsn'] ?? '';
        
        $analysis = $this->meister->analyzeProblem($symptom, $hsn, $tsn);
        
        $this->success(['analysis' => $analysis]);
    }
}

$api = new DiagnoseAPI();
$api->handleRequest();
?>