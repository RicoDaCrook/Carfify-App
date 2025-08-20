<?php
class WorkshopService {
    private $googleApiKey;
    private $geminiApiKey;
    private $cacheDir = 'cache/workshops/';
    
    public function __construct() {
        $this->googleApiKey = $_ENV['GOOGLE_MAPS_API_KEY'];
        $this->geminiApiKey = $_ENV['GEMINI_API_KEY'];
        
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
    }
    
    public function findWorkshops($lat, $lng, $radius = 10, $diagnosis = null) {
        $cacheKey = md5($lat . $lng . $radius . serialize($diagnosis));
        $cacheFile = $this->cacheDir . $cacheKey . '.json';
        
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 3600)) {
            return json_decode(file_get_contents($cacheFile), true);
        }
        
        $workshops = $this->fetchFromGoogleMaps($lat, $lng, $radius);
        $workshops = $this->analyzeReviewsWithAI($workshops);
        $workshops = $this->calculateTrends($workshops);
        $workshops = $this->calculateMatchScores($workshops, $diagnosis);
        
        file_put_contents($cacheFile, json_encode($workshops));
        return $workshops;
    }
    
    private function fetchFromGoogleMaps($lat, $lng, $radius) {
        $url = "https://maps.googleapis.com/maps/api/place/nearbysearch/json?location={$lat},{$lng}&radius=" . ($radius * 1000) . "&type=car_repair&key={$this->googleApiKey}";
        
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        
        $workshops = [];
        foreach ($data['results'] as $place) {
            $workshops[] = [
                'place_id' => $place['place_id'],
                'name' => $place['name'],
                'address' => $place['vicinity'],
                'rating' => $place['rating'] ?? 0,
                'user_ratings_total' => $place['user_ratings_total'] ?? 0,
                'location' => $place['geometry']['location'],
                'distance' => $this->calculateDistance($lat, $lng, $place['geometry']['location']['lat'], $place['geometry']['location']['lng'])
            ];
        }
        
        return $workshops;
    }
    
    private function analyzeReviewsWithAI($workshops) {
        foreach ($workshops as &$workshop) {
            $reviews = $this->getAllReviews($workshop['place_id']);
            $analysis = $this->analyzeWithGemini($reviews);
            $workshop['ai_analysis'] = $analysis;
        }
        return $workshops;
    }
    
    private function getAllReviews($placeId) {
        $url = "https://maps.googleapis.com/maps/api/place/details/json?place_id={$placeId}&fields=reviews&key={$this->googleApiKey}";
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        
        return $data['result']['reviews'] ?? [];
    }
    
    private function analyzeWithGemini($reviews) {
        if (empty($reviews)) {
            return ['pros' => [], 'cons' => [], 'price_range' => 'N/A'];
        }
        
        $reviewText = implode("\n", array_column($reviews, 'text'));
        
        $prompt = "Analysiere folgende Google-Bewertungen einer Autowerkstatt und extrahiere:
1. Die 5 häufigsten positiven Punkte mit Anzahl der Erwähnungen
2. Die 5 häufigsten negativen Punkte mit Anzahl der Erwähnungen
3. Geschätzte Preisspanne (günstig/mittel/teuer)

Bewertungen:\n{$reviewText}

Antworte im JSON-Format: {\"pros\": [{\"point\": \"...\", \"count\": X}], \"cons\": [{\"point\": \"...\", \"count\": X}], \"price_range\": \"...\"}";
        
        $ch = curl_init('https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $this->geminiApiKey);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode([
                'contents' => [['parts' => [['text' => $prompt]]]]
            ])
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        
        preg_match('/\{.*\}/s', $text, $matches);
        return json_decode($matches[0] ?? '{}', true);
    }
    
    private function calculateTrends($workshops) {
        foreach ($workshops as &$workshop) {
            $recentReviews = $this->getRecentReviews($workshop['place_id']);
            $trend = $this->calculateTrendScore($recentReviews);
            $workshop['trend'] = $trend;
        }
        return $workshops;
    }
    
    private function calculateMatchScores($workshops, $diagnosis) {
        if (!$diagnosis) return $workshops;
        
        foreach ($workshops as &$workshop) {
            $score = $this->calculateSpecializationMatch($workshop, $diagnosis);
            $workshop['match_score'] = $score;
        }
        
        usort($workshops, function($a, $b) {
            return ($b['match_score'] ?? 0) <=> ($a['match_score'] ?? 0);
        });
        
        return $workshops;
    }
    
    private function calculateDistance($lat1, $lng1, $lat2, $lng2) {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        
        $a = sin($dLat/2) * sin($dLat/2) + 
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * 
             sin($dLng/2) * sin($dLng/2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return round($earthRadius * $c, 1);
    }
    
    private function getRecentReviews($placeId) {
        $url = "https://maps.googleapis.com/maps/api/place/details/json?place_id={$placeId}&fields=reviews&reviews_sort=newest&key={$this->googleApiKey}";
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        
        return $data['result']['reviews'] ?? [];
    }
    
    private function calculateTrendScore($reviews) {
        if (empty($reviews)) return 'neutral';
        
        $sixMonthsAgo = strtotime('-6 months');
        $oneYearAgo = strtotime('-1 year');
        
        $recentRatings = [];
        $oldRatings = [];
        
        foreach ($reviews as $review) {
            $time = $review['time'];
            if ($time > $sixMonthsAgo) {
                $recentRatings[] = $review['rating'];
            } elseif ($time > $oneYearAgo && $time < $sixMonthsAgo) {
                $oldRatings[] = $review['rating'];
            }
        }
        
        if (empty($recentRatings) || empty($oldRatings)) return 'neutral';
        
        $recentAvg = array_sum($recentRatings) / count($recentRatings);
        $oldAvg = array_sum($oldRatings) / count($oldRatings);
        
        if ($recentAvg > $oldAvg + 0.3) return 'up';
        if ($recentAvg < $oldAvg - 0.3) return 'down';
        return 'stable';
    }
    
    private function calculateSpecializationMatch($workshop, $diagnosis) {
        $keywords = $this->getKeywordsForDiagnosis($diagnosis);
        $analysis = strtolower(json_encode($workshop['ai_analysis']));
        
        $matches = 0;
        foreach ($keywords as $keyword) {
            if (strpos($analysis, $keyword) !== false) {
                $matches++;
            }
        }
        
        return min(100, round(($matches / count($keywords)) * 100));
    }
    
    private function getKeywordsForDiagnosis($diagnosis) {
        $keywordMap = [
            'brake' => ['bremse', 'bremsen', 'brake', 'bremsflüssigkeit'],
            'engine' => ['motor', 'engine', 'zündkerzen', 'öl'],
            'transmission' => ['getriebe', 'transmission', 'kupplung'],
            'electrical' => ['elektrik', 'battery', 'licht', 'starter'],
            'tires' => ['reifen', 'tires', 'räder', 'achsvermessung']
        ];
        
        return $keywordMap[strtolower($diagnosis)] ?? ['auto', 'service', 'reparatur'];
    }
}