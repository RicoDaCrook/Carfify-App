<?php
require_once '../config/init.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    getWorkshops($_GET);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Methode nicht erlaubt']);
}

function getWorkshops($params) {
    global $pdo;
    
    $lat = floatval($params['lat'] ?? 52.5200);
    $lng = floatval($params['lng'] ?? 13.4050);
    $radius = intval($params['radius'] ?? 10); // km
    $specialization = $params['specialization'] ?? null;
    $page = max(1, intval($params['page'] ?? 1));
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    // Berechne Bounding Box
    $earthRadius = 6371; // km
    $latChange = $radius / $earthRadius * (180 / pi());
    $lngChange = $radius / ($earthRadius * cos(deg2rad($lat))) * (180 / pi());
    
    $latMin = $lat - $latChange;
    $latMax = $lat + $latChange;
    $lngMin = $lng - $lngChange;
    $lngMax = $lng + $lngChange;
    
    $sql = "SELECT *, 
                   (6371 * acos(cos(radians(?)) * cos(radians(lat)) * 
                   cos(radians(lng) - radians(?)) + sin(radians(?)) * 
                   sin(radians(lat)))) AS distance
            FROM workshops
            WHERE lat BETWEEN ? AND ?
            AND lng BETWEEN ? AND ?";
    
    $params = [$lat, $lng, $lat, $latMin, $latMax, $lngMin, $lngMax];
    
    if ($specialization) {
        $sql .= " AND ? = ANY(specializations)";
        $params[] = $specialization;
    }
    
    $sql .= " ORDER BY distance ASC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $workshops = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['workshops' => $workshops, 'page' => $page]);
}
?>