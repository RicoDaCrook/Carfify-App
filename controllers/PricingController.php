<?php

require_once __DIR__ . '/BaseController.php';

class PricingController extends BaseController
{
    public function calculate(): void
    {
        $vehicle = $_SESSION['carfify']['vehicle'] ?? null;
        $analysis = $_SESSION['carfify']['diagnosis'] ?? null;
        $estimate = $this->buildEstimate($vehicle, $analysis);

        $_SESSION['carfify']['pricing_estimate'] = $estimate;

        $this->render('pricing/calculate', [
            'vehicle' => $vehicle,
            'analysis' => $analysis,
            'estimate' => $estimate,
        ]);
    }

    public function process(): void
    {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $preferredDate = trim($_POST['preferred_date'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        $errors = [];
        if ($name === '') {
            $errors['name'] = 'Bitte geben Sie einen Ansprechpartner an.';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Bitte geben Sie eine gültige E-Mail-Adresse an.';
        }
        if ($preferredDate !== '' && strtotime($preferredDate) === false) {
            $errors['preferred_date'] = 'Bitte wählen Sie ein gültiges Datum.';
        }

        if ($errors) {
            http_response_code(422);
            $this->render('pricing/calculate', [
                'vehicle' => $_SESSION['carfify']['vehicle'] ?? null,
                'analysis' => $_SESSION['carfify']['diagnosis'] ?? null,
                'estimate' => $_SESSION['carfify']['pricing_estimate'] ?? null,
                'errors' => $errors,
                'old' => [
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'preferred_date' => $preferredDate,
                    'notes' => $notes,
                ]
            ]);
            return;
        }

        $request = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone ?: null,
            'preferred_date' => $preferredDate ?: null,
            'notes' => $notes ?: null,
            'estimate' => $_SESSION['carfify']['pricing_estimate'] ?? null,
            'vehicle' => $_SESSION['carfify']['vehicle'] ?? null,
            'analysis' => $_SESSION['carfify']['diagnosis'] ?? null,
            'created_at' => time(),
        ];

        if (!isset($_SESSION['carfify']) || !is_array($_SESSION['carfify'])) {
            $_SESSION['carfify'] = [];
        }
        if (!isset($_SESSION['carfify']['quotes']) || !is_array($_SESSION['carfify']['quotes'])) {
            $_SESSION['carfify']['quotes'] = [];
        }

        $_SESSION['carfify']['quotes'][] = $request;

        $this->render('pricing/confirmation', [
            'request' => $request,
            'reference' => strtoupper(substr(sha1($request['email'] . $request['created_at']), 0, 8)),
        ]);
    }

    private function buildEstimate(?array $vehicle, ?array $analysis): array
    {
        $base = 180;
        $severityFactor = is_array($analysis) ? ($analysis['severity'] ?? 3) : 3;
        $systemMultiplier = 1.0;
        $affectedSystems = is_array($analysis) ? ($analysis['affectedSystems'] ?? []) : [];

        foreach ($affectedSystems as $system) {
            switch ($system) {
                case 'motor':
                    $systemMultiplier += 0.35;
                    break;
                case 'bremsen':
                    $systemMultiplier += 0.25;
                    break;
                case 'elektronik':
                    $systemMultiplier += 0.2;
                    break;
                case 'antrieb':
                    $systemMultiplier += 0.3;
                    break;
                default:
                    $systemMultiplier += 0.1;
                    break;
            }
        }

        $severityScale = 0.8 + ($severityFactor * 0.2);
        $min = round($base * $severityScale * 0.9);
        $max = round($base * $severityScale * $systemMultiplier * 1.4);

        return [
            'range' => ['min' => $min, 'max' => max($min + 90, $max)],
            'laborHours' => round(1.5 + count($affectedSystems) * 0.8, 1),
            'partsBudget' => round($max * 0.55),
            'confidence' => min(98, 60 + ($severityFactor * 7) + (count($affectedSystems) * 4)),
            'vehicle' => $vehicle,
            'analysis' => $analysis,
        ];
    }
}
