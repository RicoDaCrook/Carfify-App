<?php
/**
 * API-Endpunkt zur Generierung eines Muster-Kaufvertrags
 */
require_once '../config/database.php';
require_once '../classes/Auth.php';

header('Content-Type: application/json');

// Authentifizierung prüfen
$auth = new Auth($pdo);
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Nicht autorisiert']);
    exit;
}

// Nur POST-Anfragen erlauben
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Methode nicht erlaubt']);
    exit;
}

// Eingabedaten validieren
$data = json_decode(file_get_contents('php://input'), true);

$requiredFields = ['vehicle_id', 'sale_price', 'buyer_name', 'buyer_address'];
foreach ($requiredFields as $field) {
    if (!isset($data[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Feld '$field' fehlt"]);
        exit;
    }
}

// Fahrzeugdaten abrufen
$stmt = $pdo->prepare("
    SELECT v.*, u.first_name, u.last_name, u.email, u.address as seller_address
    FROM vehicles v
    JOIN users u ON v.user_id = u.id
    WHERE v.id = :vehicle_id AND v.user_id = :user_id
");
$stmt->execute([
    ':vehicle_id' => $data['vehicle_id'],
    ':user_id' => $_SESSION['user_id']
]);

$vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$vehicle) {
    http_response_code(404);
    echo json_encode(['error' => 'Fahrzeug nicht gefunden']);
    exit;
}

// Vertragsvorlage generieren
$contract = "KRAFTFAHRZEUGKAUFVERTRAG

zwischen

Verkäufer:
" . $vehicle['first_name'] . " " . $vehicle['last_name'] . "
" . $vehicle['seller_address'] . "
E-Mail: " . $vehicle['email'] . "

und

Käufer:
" . $data['buyer_name'] . "
" . $data['buyer_address'] . "

wird nachstehendes Kraftfahrzeug verkauft:

1. Fahrzeugdaten:
   Marke/Modell: " . $vehicle['make'] . " " . $vehicle['model'] . "
   Erstzulassung: " . $vehicle['year'] . "
   Fahrgestell-Nr.: " . $vehicle['vin'] . "
   Kennzeichen: " . $vehicle['license_plate'] . "
   Kilometerstand: " . number_format($vehicle['mileage'], 0, ',', '.') . " km
   Farbe: " . $vehicle['color'] . "

2. Kaufpreis:
   Der Kaufpreis beträgt: " . number_format($data['sale_price'], 2, ',', '.') . " EUR
   (in Worten: " . numberToWords($data['sale_price']) . " Euro)

3. Zahlungsbedingungen:
   Der Kaufpreis ist bei Übergabe des Fahrzeugs in bar zu zahlen.

4. Übergabe:
   Ort und Datum der Übergabe: _________________________
   Zeitpunkt der Übergabe: ____________________________

5. Haftungsausschluss:
   Der Verkäufer übernimmt keine Haftung für nach der Übergabe entdeckte Mängel.
   Das Fahrzeug wird verkauft unter Ausschluss jeglicher Sachmängelhaftung.

6. Sonstiges:
   - Die Zulassung des Fahrzeugs obliegt dem Käufer
   - Die Kosten für die Umschreibung trägt der Käufer
   - Der Verkäufer bestätigt, dass das Fahrzeug frei von Pfandrechten ist

Ort und Datum: _________________________

_________________________           _________________________
(Verkäufer)                          (Käufer)

HINWEIS: Dies ist ein Muster-Kaufvertrag. Für rechtliche Beratung konsultieren Sie bitte einen Anwalt.";

// PDF-Generierung (simuliert - in Produktion würde eine PDF-Bibliothek verwendet)
$pdfUrl = '/downloads/contract_' . $vehicle['id'] . '_' . time() . '.pdf';

// Antwort vorbereiten
$response = [
    'contract_text' => $contract,
    'pdf_url' => $pdfUrl,
    'vehicle' => [
        'make' => $vehicle['make'],
        'model' => $vehicle['model'],
        'year' => $vehicle['year'],
        'vin' => $vehicle['vin']
    ],
    'sale_price' => $data['sale_price'],
    'buyer' => [
        'name' => $data['buyer_name'],
        'address' => $data['buyer_address']
    ]
];

echo json_encode($response);

// Hilfsfunktion zur Zahlwort-Konvertierung
function numberToWords($number) {
    $words = "";
    $number = intval($number);
    
    if ($number == 0) {
        return "null";
    }
    
    if ($number < 0) {
        $words = "minus ";
        $number = abs($number);
    }
    
    $units = ["", "eins", "zwei", "drei", "vier", "fünf", "sechs", "sieben", "acht", "neun"];
    $teens = ["zehn", "elf", "zwölf", "dreizehn", "vierzehn", "fünfzehn", "sechzehn", "siebzehn", "achtzehn", "neunzehn"];
    $tens = ["", "zehn", "zwanzig", "dreißig", "vierzig", "fünfzig", "sechzig", "siebzig", "achtzig", "neunzig"];
    
    if ($number >= 1000) {
        $thousands = intval($number / 1000);
        $words .= $units[$thousands] . "tausend";
        $number %= 1000;
    }
    
    if ($number >= 100) {
        $hundreds = intval($number / 100);
        $words .= $units[$hundreds] . "hundert";
        $number %= 100;
    }
    
    if ($number >= 20) {
        $tensDigit = intval($number / 10);
        $unitsDigit = $number % 10;
        if ($unitsDigit > 0) {
            $words .= $units[$unitsDigit] . "und" . $tens[$tensDigit];
        } else {
            $words .= $tens[$tensDigit];
        }
    } elseif ($number >= 10) {
        $words .= $teens[$number - 10];
    } elseif ($number > 0) {
        $words .= $units[$number];
    }
    
    return $words;
}
