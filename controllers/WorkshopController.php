<?php

require_once __DIR__ . '/BaseController.php';

class WorkshopController extends BaseController
{
    private array $workshops;

    public function __construct()
    {
        $this->workshops = $this->loadWorkshops();
    }

    public function search(): void
    {
        $filters = $_SESSION['carfify']['last_workshop_filter'] ?? [
            'radius' => 25,
            'type' => '',
            'specialization' => '',
        ];

        $results = $this->applyFilters($filters);

        $this->render('workshop/search', [
            'vehicle' => $_SESSION['carfify']['vehicle'] ?? null,
            'analysis' => $_SESSION['carfify']['diagnosis'] ?? null,
            'filters' => $filters,
            'workshops' => $results,
            'analytics' => $this->buildAnalytics($results)
        ]);
    }

    public function filter(): void
    {
        $filters = [
            'radius' => (int)($_POST['radius'] ?? 25),
            'type' => trim($_POST['type'] ?? ''),
            'specialization' => trim($_POST['specialization'] ?? ''),
        ];

        if (!isset($_SESSION['carfify']) || !is_array($_SESSION['carfify'])) {
            $_SESSION['carfify'] = [];
        }

        $_SESSION['carfify']['last_workshop_filter'] = $filters;

        $results = $this->applyFilters($filters);

        $this->render('workshop/search', [
            'vehicle' => $_SESSION['carfify']['vehicle'] ?? null,
            'analysis' => $_SESSION['carfify']['diagnosis'] ?? null,
            'filters' => $filters,
            'workshops' => $results,
            'analytics' => $this->buildAnalytics($results)
        ]);
    }

    private function applyFilters(array $filters): array
    {
        return array_values(array_filter($this->workshops, static function (array $workshop) use ($filters) {
            if ($filters['type'] && $filters['type'] !== $workshop['type']) {
                return false;
            }
            if ($filters['specialization'] && stripos(implode(' ', $workshop['specializations']), $filters['specialization']) === false) {
                return false;
            }
            if ($filters['radius'] && $workshop['distance'] > $filters['radius']) {
                return false;
            }
            return true;
        }));
    }

    private function buildAnalytics(array $workshops): array
    {
        if (!$workshops) {
            return [
                'count' => 0,
                'averageRating' => null,
                'availableTypes' => [],
            ];
        }

        $ratings = array_column($workshops, 'rating');
        $types = array_count_values(array_column($workshops, 'type'));

        return [
            'count' => count($workshops),
            'averageRating' => round(array_sum($ratings) / max(count($ratings), 1), 2),
            'availableTypes' => $types,
        ];
    }

    private function loadWorkshops(): array
    {
        $path = __DIR__ . '/../storage/data/workshops.php';
        if (!file_exists($path)) {
            throw new RuntimeException('Werkstattdaten fehlen.');
        }

        $data = require $path;
        if (!is_array($data)) {
            throw new RuntimeException('Werkstattdaten konnten nicht interpretiert werden.');
        }

        return $data;
    }
}
