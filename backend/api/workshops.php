<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

function searchWorkshops($lat, $lng, $radius = 5000, $repairType = 'all') {
    $apiKey = $_ENV['GOOGLE_MAPS_API_KEY'] ?? '';
    
    // Convert radius from meters to kilometers
    $radiusKm = $radius / 1000;
    
    // Build query for Google Places API
    $query = urlencode("car repair auto repair workshop");
    $url = "https://maps.googleapis.com/maps/api/place/textsearch/json?query={$query}&location={$lat},{$lng}&radius={$radius}&key={$apiKey}";
    
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    
    $workshops = [];
    
    if ($data['status'] === 'OK' && isset($data['results'])) {
        foreach ($data['results'] as $place) {
            $details = getPlaceDetails($place['place_id'], $apiKey);
            
            $workshop = [
                'id' => $place['place_id'],
                'name' => $place['name'],
                'address' => $place['formatted_address'],
                'rating' => $place['rating'] ?? null,
                'ratingCount' => $place['user_ratings_total'] ?? 0,
                'latitude' => $place['geometry']['location']['lat'],
                'longitude' => $place['geometry']['location']['lng'],
                'phone' => $details['phone'] ?? null,
                'website' => $details['website'] ?? null,
                'openNow' => $place['opening_hours']['open_now'] ?? null,
                'photo' => $details['photo'] ?? null,
                'priceLevel' => $place['price_level'] ?? null,
                'distance' => calculateDistance($lat, $lng, $place['geometry']['location']['lat'], $place['geometry']['location']['lng'])
            ];
            
            // Filter by repair type if specified (implementation would go here)
            $workshops[] = $workshop;
        }
        
        // Sort by distance
        usort($workshops, function($a, $b) {
            return $a['distance'] <=> $b['distance'];
        });
    }
    
    return $workshops;
}

function getPlaceDetails($placeId, $apiKey) {
    $url = "https://maps.googleapis.com/maps/api/place/details/json?place_id={$placeId}&fields=name,formatted_phone_number,website,photos&key={$apiKey}";
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    
    $details = [];
    
    if ($data['status'] === 'OK' && isset($data['result'])) {
        $details['phone'] = $data['result']['formatted_phone_number'] ?? null;
        $details['website'] = $data['result']['website'] ?? null;
        
        if (isset($data['result']['photos'][0])) {
            $photo = $data['result']['photos'][0];
            $details['photo'] = "https://maps.googleapis.com/maps/api/place/photo?maxwidth=400&photoreference={$photo['photo_reference']}&key={$apiKey}";
        }
    }
    
    return $details;
}

function calculateDistance($lat1, $lng1, $lat2, $lng2) {
    $earthRadius = 6371000; // meters
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLng/2) * sin($dLng/2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return round($earthRadius * $c / 1000, 1); // return in kilometers
}

// Handle API requests
try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            $lat = $_GET['lat'] ?? null;
            $lng = $_GET['lng'] ?? null;
            $radius = $_GET['radius'] ?? 5000;
            $type = $_GET['type'] ?? 'all';
            
            if (!$lat || !$lng) {
                throw new Exception('Latitude and Longitude are required');
            }
            
            $lat = floatval($lat);
            $lng = floatval($lng);
            $radius = intval($radius);
            
            $workshops = searchWorkshops($lat, $lng, $radius, $type);
            
            echo json_encode([
                'success' => true,
                'data' => $workshops,
                'count' => count($workshops)
            ]);
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data || !isset($data['lat']) || !isset($data['lng'])) {
                throw new Exception('Invalid input data');
            }
            
            $lat = floatval($data['lat']);
            $lng = floatval($data['lng']);
            $radius = isset($data['radius']) ? intval($data['radius']) : 5000;
            $type = $data['type'] ?? 'all';
            
            $workshops = searchWorkshops($lat, $lng, $radius, $type);
            
            echo json_encode([
                'success' => true,
                'data' => $workshops,
                'count' => count($workshops)
            ]);
            break;
            
        default:
            throw new Exception('Method not allowed');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
