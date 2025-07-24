<?php
namespace Carfify\Classes;

use RuntimeException;

class Diagnosis {
    private array $symptoms;
    private array $possibleCauses;

    public function __construct(array $symptoms) {
        $this->symptoms = $symptoms;
        $this->analyzeSymptoms();
    }

    private function analyzeSymptoms(): void {
        // KI-Überprüfung – nur möglich, wenn ENV vorhanden
        if (!empty($_ENV['GEMINI_API_KEY'])) {
            $aiSuggestion = $this->queryGemini();
            if ($aiSuggestion) {
                $this->possibleCauses = $aiSuggestion;
                return;
            }
        }
        // Fallback
        $this->possibleCauses = $this->fallbackAnalysis();
    }

    private function queryGemini(): ?array {
        try {
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' .
                   rawurlencode($_ENV['GEMINI_API_KEY']);

            $payload = [
                'contents' => [[
                    'parts' => [
                        ['text' => 'Erstelle eine kurze Liste der Top-3 Ursachen folgender Autosymptome: ' .
                                   implode(', ', $this->symptoms) .
                                   '. Gib nur eine JSON-Liste zurück: [{"title":"...","description":"..."},...]']
                    ]
                ]]
            ];

            $response = $this->curlPost($url, json_encode($payload), ['Content-Type: application/json']);
            $decoded = json_decode($response, true);

            return $decoded['candidates'][0]['content']['parts'][0]['text'] ?? null;
        } catch (\Throwable $e) {
            error_log('Gemini API Error: ' . $e->getMessage());
            return null;
        }
    }

    private function fallbackAnalysis(): array {
        // einfache Regelbasierte Zuordnung
        $map = [
            'quiet' => [
                ['title' => 'Leere oder defekte Batterie', 'description' => 'Überprüfen Batteriesäure und Ladestand.'],
                ['title' => 'Problem mit der Lichtmaschine', 'description' => 'Kontrolliere Keilriemen und Klemmen.'],
                ['title' => 'Defekte Sicherung', 'description' => 'Prüfe Sicherung für Starter- und Unterbrecherkreis.']
            ],
            'clicking' => [
                ['title' => 'Niedriger Batterieladestand', 'description' => 'Startversprechen durch Absicherungsklemme testen.'],
                ['title' => 'Starter-Relais defekt', 'description' => 'Wechsel Relais oder reinige Kontakte.'],
                ['title' => 'Defekter Anlasser', 'description' => 'Lass Mechaniker startmotor prüfen.']
            ],
            'cranking' => [
                ['title' => 'Fehlender Zündfunken', 'description' => 'Prüfe Zündspule und Kerzenkabel.'],
                ['title' => 'Problem mit Kraftstoffsystem', 'description' => 'Kontrolliere Kraftstoffpumpe und Filter.'],
                ['title' => 'Defekte Zündkerzen', 'description' => 'Lass Kerzen auf Verschleiß prüfen.']
            ],
            'smell' => [
                ['title' => 'Kühlmittellack', 'description' => 'Überhitzung – Kühlmittel prüfen.'],
                ['title' => 'Ölstand unklar', 'description' => 'Konsistenz unklar – Ölwechsel kontrollieren.'],
                ['title' => 'Auspuffleckage', 'description' => 'Abgaskreislauf auf Löcher untersuchen.']
            ]
        ];

        $result = [];
        foreach ($this->symptoms as $s) {
            $sLower = mb_strtolower($s);
            if (isset($map[$sLower])) {
                foreach ($map[$sLower] as $cause) {
                    // vermeide doppelte
                    if (!in_array($cause, $result, true)) {
                        $result[] = $cause;
                    }
                }
            }
        }
        // weniger Rückgaben → Top 3
        return array_slice($result, 0, 3);
    }

    public function getResults(): array {
        return [
            'symptoms'      => $this->symptoms,
            'possibleCauses'=> $this->possibleCauses
        ];
    }

    private function curlPost(string $url, string $data, array $headers = []): string {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $data,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 8,
        ]);
        $response = curl_exec($ch);
        if ($response === false) {
            throw new RuntimeException(curl_error($ch));
        }
        curl_close($ch);
        return $response;
    }
}
