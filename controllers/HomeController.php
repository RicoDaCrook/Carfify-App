<?php

require_once __DIR__ . '/BaseController.php';

class HomeController extends BaseController
{
    public function landingPage(): void
    {
        $data = [
            'title' => 'Carfify - KI-Fahrzeugdiagnose',
            'features' => [
                [
                    'title' => 'Fahrzeugdiagnose',
                    'description' => 'KI-gestützte Fehleranalyse für Ihr Fahrzeug',
                    'icon' => 'diagnosis-icon',
                    'url' => '/fahrzeug-auswahl',
                    'color' => 'blue'
                ],
                [
                    'title' => 'Werkstattsuche',
                    'description' => 'Finden Sie die passende Werkstatt in Ihrer Nähe',
                    'icon' => 'workshop-icon',
                    'url' => '/werkstatt-suche',
                    'color' => 'green'
                ],
                [
                    'title' => 'Preiskalkulation',
                    'description' => 'Berechnen Sie Reparaturkosten im Voraus',
                    'icon' => 'pricing-icon',
                    'url' => '/preis-kalkulation',
                    'color' => 'orange'
                ],
                [
                    'title' => 'Fahrzeugdatenbank',
                    'description' => 'Zugriff auf umfangreiche Fahrzeuginformationen',
                    'icon' => 'database-icon',
                    'url' => '/fahrzeug-suche',
                    'color' => 'purple'
                ]
            ],
            'stats' => [
                'diagnosed_vehicles' => 15420,
                'workshops' => 1250,
                'accuracy' => 94.7,
                'satisfied_users' => 9876
            ]
        ];

        $this->render('home/landing-page', $data);
    }
}
