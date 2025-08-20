<?php
/**
 * API-Endpunkt für das Detektiv-Diagnose-System
 */

require_once 'detektiv-diagnose.php';

$diagnose = new DetektivDiagnose();
header('Content-Type: application/json');
echo $diagnose->handleApiRequest();
?>