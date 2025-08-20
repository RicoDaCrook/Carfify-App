<?php
/**
 * Test-Datei für das Detektiv-Diagnose-System
 */

session_start();
$_SESSION['diagnose'] = [
    'fahrzeug' => [
        'marke' => 'VW',
        'modell' => 'Golf VII',
        'baujahr' => 2015,
        'kilometer' => 85000
    ],
    'kategorie' => 'motor',
    'sicherheit' => 0
];

include 'detektiv-diagnose.php';

$diagnose = new DetektivDiagnose();
echo $diagnose->renderDiagnoseInterface();
?>