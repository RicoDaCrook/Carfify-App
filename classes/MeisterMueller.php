<?php
class MeisterMueller {
    private $apiKey;
    
    public function __construct() {
        $this->apiKey = $_ENV['CLAUDE_API_KEY'] ?? '';
    }
    
    public function generateResponse($message, $context = []) {
        // Für Demo ohne API - später mit Claude API
        return $this->generateDemoResponse($message, $context);
    }
    
    private function generateDemoResponse($message, $context) {
        $message = strtolower($message);
        
        // Einfache Pattern-Matching für Demo
        if (strpos($message, 'öl') !== false) {
            return "Denken Sie an den Ölwechsel wie an das Wechseln der Kaffeefilter - regelmäßig und wichtig! <br><br>
                   <strong>So prüfen Sie den Ölstand:</strong><br>
                   1. Motor ausschalten und 5 Minuten warten<br>
                   2. Gelben Messstab ziehen (links am Motor)<br>
                   3. Abwischen, wieder einführen, rausziehen<br>
                   4. Öl sollte zwischen den Markierungen sein<br><br>
                   🚨 Wenn es unter Minimum ist: Sofort nachfüllen!";
        }
        
        if (strpos($message, 'bremse') !== false || strpos($message, 'quietschen') !== false) {
            return "Quietschende Bremsen sind wie ein schreiendes Baby - sie wollen Aufmerksamkeit! <br><br>
                   <strong>Mögliche Ursachen:</strong><br>
                   • Verschleißanzeiger (ganz normal)<br>
                   • Zu dünne Bremsbeläge<br>
                   • Rost auf den Bremsscheiben<br><br>
                   <strong>Was Sie tun können:</strong><br>
                   1. Bremsflüssigkeit prüfen (Behälter mit 'BRAKE' beschriftet)<br>
                   2. Durch die Felgen schauen - Beläge sollten 3mm dick sein<br>
                   3. Leichtes Quietschen beim ersten Bremsen ist normal (Rost abbrechen)";
        }
        
        if (strpos($message, 'motor') !== false && strpos($message, 'geräusch') !== false) {
            return "Motorgeräusche sind wie Bauchschmerzen - wichtig ist, woher sie kommen! <br><br>
                   <strong>Unterscheiden Sie:</strong><br>
                   • <strong>Klappern beim Start:</strong> Keilriemen prüfen<br>
                   • <strong>Ticken im Leerlauf:</strong> Ventile oder Ölmangel<br>
                   • <strong>Brummen beim Beschleunigen:</strong> Auspuff oder Lager<br><br>
                   <strong>Schnell-Check:</strong><br>
                   1. Motorhaube öffnen (Hebel links unter Lenkrad)<br>
                   2. Beim laufenden Motor zuhören - wo kommt das Geräusch her?<br>
                   3. Keilriemen anschauen - keine Risse oder Lockerheit";
        }
        
        // Standard-Antwort
        return "Das ist eine gute Frage! Als Meister erkläre ich Ihnen das ganz einfach: <br><br>
               Stellen Sie mir bitte genauer, was Ihr Auto macht. Zum Beispiel:<br>
               • Hören Sie ein Geräusch? Woher?<br>
               • Leuchtet eine Lampe?<br>
               • Fühlt sich das Fahren anders an?<br><br>
               Je mehr Details Sie mir geben, desto besser kann ich helfen!";
    }
    
    public function analyzeProblem($symptom, $hsn, $tsn) {
        // Analyse für spezifisches Symptom
        $analyses = [
            'engine_noise' => [
                'likely_causes' => ['Keilriemen', 'Ölmangel', 'Ventile'],
                'urgency' => 'hoch',
                'description' => 'Motorgeräusche sollten schnell geprüft werden'
            ],
            'brake_squeal' => [
                'likely_causes' => ['Verschleißanzeiger', 'Dünne Beläge', 'Rost'],
                'urgency' => 'mittel',
                'description' => 'Bremsen prüfen lassen, aber nicht sofort gefährlich'
            ],
            'oil_light' => [
                'likely_causes' => ['Ölmangel', 'Öldruck', 'Sensor'],
                'urgency' => 'sehr hoch',
                'description' => 'SOFORT anhalten und prüfen!'
            ]
        ];
        
        return $analyses[$symptom] ?? [
            'likely_causes' => ['Weitere Diagnose nötig'],
            'urgency' => 'unbekannt',
            'description' => 'Bitte genauer beschreiben'
        ];
    }
    
    public function getBeginnerExplanation($technicalTerm) {
        $explanations = [
            'bremsscheibe' => 'Das ist wie eine Pizza aus Metall - die Bremse klemmt dazwischen und macht das Auto langsamer',
            'keilriemen' => 'Stellen Sie sich einen Gummiriemen vor, der verschiedene Teile des Motors verbindet wie eine Fahrradkette',
            'ventile' => 'Das sind wie kleine Türen im Motor, die Luft und Benzin rein- und rauslassen',
            'ölfilter' => 'Das ist wie ein Kaffeefilter, aber für Motoröl - hält Schmutz zurück'
        ];
        
        return $explanations[strtolower($technicalTerm)] ?? 
               'Das erkläre ich Ihnen gern genauer - wofür genau interessieren Sie sich?';
    }
}
?>