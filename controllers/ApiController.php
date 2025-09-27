<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/DiagnosisController.php';

class ApiController extends BaseController
{
    private array $vehicles;
    private array $workshops;

    public function __construct()
    {
        $this->vehicles = $this->loadData('vehicles.php', 'Fahrzeug');
        $this->workshops = $this->loadData('workshops.php', 'Werkstatt');
    }

    public function getVehicles(): void
    {
        $query = trim($_GET['q'] ?? '');
        $vehicles = array_values(array_filter($this->vehicles, static function (array $vehicle) use ($query) {
            if ($query === '') {
                return true;
            }
            $haystack = strtolower($vehicle['brand'] . ' ' . $vehicle['model']);
            return strpos($haystack, strtolower($query)) !== false;
        }));

        $this->json([
            'vehicles' => $vehicles,
            'count' => count($vehicles)
        ]);
    }

    public function getWorkshops(): void
    {
        $type = trim($_GET['type'] ?? '');
        $specialization = trim($_GET['specialization'] ?? '');

        $filtered = array_values(array_filter($this->workshops, static function (array $workshop) use ($type, $specialization) {
            if ($type && $workshop['type'] !== $type) {
                return false;
            }
            if ($specialization && stripos(implode(' ', $workshop['specializations']), $specialization) === false) {
                return false;
            }
            return true;
        }));

        $this->json([
            'workshops' => $filtered,
            'count' => count($filtered)
        ]);
    }

    public function diagnose(): void
    {
        $payload = $this->requireJsonPayload();
        $symptoms = trim($payload['symptoms'] ?? '');
        $severity = (int)($payload['severity'] ?? 3);
        $vehicleId = (string)($payload['vehicle_id'] ?? '');

        if ($symptoms === '' || !isset($this->vehicles[$vehicleId])) {
            $this->json([
                'error' => 'Bitte übermitteln Sie Symptome und ein bekanntes Fahrzeug.'
            ], 422);
        }

        $systems = (new DiagnosisController())->processProblemViaApi($symptoms, $severity, $payload);

        $this->json([
            'vehicle' => $this->vehicles[$vehicleId],
            'symptoms' => $symptoms,
            'severity' => $severity,
            'systems' => $systems,
        ]);
    }

    private function loadData(string $file, string $label): array
    {
        $path = __DIR__ . '/../storage/data/' . $file;
        if (!file_exists($path)) {
            throw new RuntimeException($label . 'daten nicht gefunden.');
        }

        $data = require $path;
        if (!is_array($data)) {
            throw new RuntimeException($label . 'daten konnten nicht gelesen werden.');
        }

        if ($file === 'vehicles.php') {
            $normalized = [];
            foreach ($data as $vehicle) {
                $normalized[(string)$vehicle['id']] = $vehicle;
            }
            return $normalized;
        }

        return $data;
    }
}
