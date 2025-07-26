<?php
require_once '../config/init.php';

// GET-Anfrage für Werkstätten
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Methode nicht erlaubt']);
    exit();
}

// Parameter
$lat = isset($_GET['lat']) ? (float)$_GET['lat'] : 52.5200;
$lng = isset($_GET['lng']) ? (float)$_GET['lng'] : 13.4050;
$radius = isset($_GET['radius']) ? (int)$_GET['radius'] : 25; // km
$specialty = isset($_GET['specialty']) ? sanitizeInput($_GET['specialty']) : null;

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

try {
    // SQL-Query mit Entfernungsberechnung
    $sql = "SELECT *, 
            (6371 * acos(
                cos(radians(?)) * cos(radians(lat)) * 
                cos(radians(lng) - radians(?)) + 
                sin(radians(?)) * sin(radians(lat))
            )) AS distance
            FROM workshops
            WHERE (6371 * acos(
                cos(radians(?)) * cos(radians(lat)) * 
                cos(radians(lng) - radians(?)) + 
                sin(radians(?)) * sin(radians(lat))
            )) <= ?";

    $params = [$lat, $lng, $lat, $lat, $lng, $lat, $radius];

    // Optional nach Spezialisierung filtern
    if ($specialty) {
        $sql .= " AND specialties @> ARRAY[?]";
        $params[] = $specialty;
    }

    $sql .= " ORDER BY distance ASC, rating DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $workshops = $stmt->fetchAll();

    // Gesamtzahl für Pagination
    $count_sql = "SELECT COUNT(*) as total FROM workshops WHERE (6371 * acos(...)) <= ?";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute([$lat, $lng, $lat, $radius]);
    $total = $count_stmt->fetch()['total'];

    echo json_encode([
        'success' => true,
        'workshops' => $workshops,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => (int)$total,
            'pages' => ceil($total / $limit)
        ]
    ]);

} catch (Exception $e) {
    logError('Werkstatt-Suche Fehler', ['error' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode(['error' => 'Werkstätten konnten nicht geladen werden']);
}