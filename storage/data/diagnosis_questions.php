<?php

return [
    'allgemein' => [
        'title' => 'Allgemeine Rückfragen',
        'questions' => [
            [
                'id' => 'start_condition',
                'text' => 'Tritt das Problem in kaltem oder warmem Zustand häufiger auf?',
                'impact' => 'hoch',
                'hint' => 'Temperaturabhängige Probleme deuten häufig auf Sensorik oder Gemischaufbereitung hin.'
            ],
            [
                'id' => 'warning_lights',
                'text' => 'Leuchtet eine Warnlampe im Cockpit oder wurden Fehlermeldungen angezeigt?',
                'impact' => 'hoch',
                'hint' => 'Fehlermeldungen liefern direkte Hinweise auf die betroffenen Steuergeräte.'
            ],
            [
                'id' => 'recent_service',
                'text' => 'Gab es kürzlich einen Werkstattaufenthalt oder Software-Updates?',
                'impact' => 'mittel',
                'hint' => 'Nach Arbeiten können Bauteile nachjustiert werden müssen.'
            ],
        ],
    ],
    'motor' => [
        'title' => 'Motor & Einspritzung',
        'questions' => [
            [
                'id' => 'engine_noise',
                'text' => 'Sind ungewöhnliche Motorgeräusche oder Vibrationen spürbar?',
                'impact' => 'mittel',
                'hint' => 'Mechanische Geräusche lassen auf Lager- oder Einspritzthemen schließen.'
            ],
            [
                'id' => 'power_loss',
                'text' => 'Verliert das Fahrzeug spürbar an Leistung, z. B. beim Überholen?',
                'impact' => 'hoch',
                'hint' => 'Leistungsverlust bei Last deutet auf Luftmassenmesser oder Turbosystem hin.'
            ],
            [
                'id' => 'cold_start',
                'text' => 'Gibt es Startschwierigkeiten nach längeren Standzeiten?',
                'impact' => 'mittel',
                'hint' => 'Kraftstoffdruck und Batteriespannung prüfen lassen.'
            ],
        ],
    ],
    'bremsen' => [
        'title' => 'Bremsanlage',
        'questions' => [
            [
                'id' => 'brake_noise',
                'text' => 'Treten schleifende oder quietschende Geräusche beim Bremsen auf?',
                'impact' => 'mittel',
                'hint' => 'Abnutzung oder verglaste Beläge prüfen lassen.'
            ],
            [
                'id' => 'abs_activity',
                'text' => 'Regelt das ABS auch bei normalem Bremsen spürbar?',
                'impact' => 'hoch',
                'hint' => 'Sensoren oder Raddrehzahlsignale können fehlerhaft sein.'
            ],
        ],
    ],
    'elektronik' => [
        'title' => 'Elektronik & Software',
        'questions' => [
            [
                'id' => 'infotainment',
                'text' => 'Fallen Infotainment- oder Assistenzsysteme sporadisch aus?',
                'impact' => 'mittel',
                'hint' => 'Softwarestände und Steuergeräteverbindungen prüfen.'
            ],
            [
                'id' => 'battery_state',
                'text' => 'Gab es zuletzt Probleme mit der Bordspannung oder Batterie?',
                'impact' => 'mittel',
                'hint' => 'Ladespannung und Energiemanagement analysieren.'
            ],
        ],
    ],
    'antrieb' => [
        'title' => 'Getriebe & Antriebsstrang',
        'questions' => [
            [
                'id' => 'shift_quality',
                'text' => 'Sind Schaltvorgänge verzögert oder ruckartig?',
                'impact' => 'hoch',
                'hint' => 'Adaptionswerte und Ölzustand prüfen lassen.'
            ],
            [
                'id' => 'drivetrain_noise',
                'text' => 'Treten Geräusche bei Lastwechsel oder Kurvenfahrt auf?',
                'impact' => 'mittel',
                'hint' => 'Kardanwelle und Differenzial auf Spiel prüfen.'
            ],
        ],
    ],
];
