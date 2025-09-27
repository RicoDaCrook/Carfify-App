<?php

require_once __DIR__ . '/BaseController.php';

class VehicleController extends BaseController
{
    private array $vehicles;

    public function __construct()
    {
        $this->vehicles = $this->loadVehicles();
    }

    public function selection(): void
    {
        $selection = $_SESSION['carfify']['vehicle'] ?? null;
        $this->render('vehicle/selection', [
            'vehicles' => $this->vehicles,
            'selection' => $selection,
            'error' => null
        ]);
    }

    public function processSelection(): void
    {
        $vehicleId = $_POST['vehicle_id'] ?? '';

        if (!$vehicleId || !isset($this->vehicles[$vehicleId])) {
            http_response_code(422);
            $this->render('vehicle/selection', [
                'vehicles' => $this->vehicles,
                'selection' => $_SESSION['carfify']['vehicle'] ?? null,
                'error' => 'Bitte wählen Sie eines der hinterlegten Fahrzeuge aus.'
            ]);
            return;
        }

        if (!isset($_SESSION['carfify']) || !is_array($_SESSION['carfify'])) {
            $_SESSION['carfify'] = [];
        }

        $_SESSION['carfify']['vehicle'] = $this->vehicles[$vehicleId];
        $_SESSION['carfify']['vehicle']['selected_at'] = time();

        $this->render('vehicle/summary', [
            'vehicle' => $_SESSION['carfify']['vehicle'],
            'recommendedRoutes' => [
                [
                    'title' => 'Symptome beschreiben',
                    'description' => 'Beschreiben Sie die aktuellen Auffälligkeiten, damit wir gezielte Prüfpfade vorbereiten können.',
                    'url' => '/problem-beschreibung',
                    'icon' => 'fa-stethoscope'
                ],
                [
                    'title' => 'Intelligente KI-Analyse',
                    'description' => 'Unsere Diagnose-KI bewertet die Symptome und erstellt eine priorisierte Fehlerhypothese.',
                    'url' => '/ki-analyse',
                    'icon' => 'fa-robot'
                ],
                [
                    'title' => 'Werkstätten vergleichen',
                    'description' => 'Vergleichen Sie passende Partnerwerkstätten mitsamt Erfahrungswerten und Anfahrtszeiten.',
                    'url' => '/werkstatt-suche',
                    'icon' => 'fa-screwdriver-wrench'
                ],
            ],
        ]);
    }

    public function search(): void
    {
        $filters = [
            'brand' => trim($_GET['brand'] ?? ''),
            'model' => trim($_GET['model'] ?? ''),
            'fuel_type' => trim($_GET['fuel_type'] ?? ''),
            'year' => trim($_GET['year'] ?? ''),
        ];

        $results = array_filter($this->vehicles, static function (array $vehicle) use ($filters) {
            if ($filters['brand'] && stripos($vehicle['brand'], $filters['brand']) === false) {
                return false;
            }
            if ($filters['model'] && stripos($vehicle['model'], $filters['model']) === false) {
                return false;
            }
            if ($filters['fuel_type'] && stripos($vehicle['fuel_type'], $filters['fuel_type']) === false) {
                return false;
            }
            if ($filters['year'] && (int)$filters['year'] !== (int)$vehicle['year']) {
                return false;
            }
            return true;
        });

        $this->render('vehicle/search', [
            'vehicles' => $results,
            'filters' => $filters,
            'totalVehicles' => count($this->vehicles)
        ]);
    }

    private function loadVehicles(): array
    {
        $path = __DIR__ . '/../storage/data/vehicles.php';
        if (!file_exists($path)) {
            throw new RuntimeException('Die Fahrzeugdaten konnten nicht geladen werden.');
        }

        $data = require $path;
        if (!is_array($data)) {
            throw new RuntimeException('Die Fahrzeugdaten sind beschädigt.');
        }

        $normalized = [];
        foreach ($data as $vehicle) {
            $normalized[(string)$vehicle['id']] = $vehicle;
        }

        return $normalized;
    }
}
