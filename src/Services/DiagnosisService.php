<?php
namespace Carfify\Services;

use Carfify\Config\Database;

class DiagnosisService
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function analyzeSymptoms(array $symptoms): array
    {
        // KI-basierte Diagnose-Logik hier
        $diagnosis = [
            'issues' => [],
            'severity' => 'low',
            'estimated_cost' => 0,
            'recommendations' => []
        ];
        
        // Beispiel-Logik
        foreach ($symptoms as $symptom) {
            switch ($symptom) {
                case 'engine_noise':
                    $diagnosis['issues'][] = 'Möglicher Zahnriemenverschleiß';
                    $diagnosis['estimated_cost'] += 800;
                    break;
                case 'oil_leak':
                    $diagnosis['issues'][] = 'Ölverlust - Dichtung prüfen';
                    $diagnosis['estimated_cost'] += 200;
                    break;
            }
        }
        
        return $diagnosis;
    }
    
    public function getMasterMuellerResponse(array $diagnosis): string
    {
        return "Hallo, ich bin Meister Müller. " . 
               "Basierend auf Ihrer Analyse: " . 
               implode(', ', $diagnosis['issues']) . 
               ". Geschätzte Reparaturkosten: " . 
               $diagnosis['estimated_cost'] . "€";
    }
}