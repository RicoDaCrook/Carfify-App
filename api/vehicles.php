<?php
require_once '../config/init.php';

// GET-Fahrzeugliste
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Methode nicht erlaubt']);
    exit();
}

$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : null;

// Fahrzeuge abrufen
$sql = "SELECT * FROM vehicles WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (make ILIKE ? OR model ILIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY make, model, year DESC LIMIT 50";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $vehicles = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'vehicles' => $vehicles
    ]);

} catch (Exception $e) {
    logError('Fahrzeug-Abruf Fehler', ['error' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode(['error' => 'Fahrzeuge konnten nicht geladen werden']);
}