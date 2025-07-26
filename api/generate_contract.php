<?php
require_once '../config/init.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    generateContract($_POST);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Methode nicht erlaubt']);
}

function generateContract($data) {
    $vehicleId = intval($data['vehicle_id'] ?? 0);
    $sellerData = json_decode($data['seller_data'] ?? '{}', true);
    $buyerData = json_decode($data['buyer_data'] ?? '{}', true);
    $price = floatval($data['price'] ?? 0);
    
    if (!$vehicleId || !$sellerData || !$price) {
        http_response_code(400);
        echo json_encode(['error' => 'Fehlende Daten']);
        return;
    }
    
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
    $stmt->execute([$vehicleId]);
    $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$vehicle) {
        http_response_code(404);
        echo json_encode(['error' => 'Fahrzeug nicht gefunden']);
        return;
    }
    
    // HTML-Kaufvertrag generieren
    $contract = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Kaufvertrag</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 40px; }
            .contract { max-width: 800px; margin: 0 auto; }
            h1 { text-align: center; }
            .section { margin: 20px 0; }
            .signature { margin-top: 50px; display: flex; justify-content: space-between; }
        </style>
    </head>
    <body>
        <div class='contract'>
            <h1>Kaufvertrag für Kraftfahrzeug</h1>
            
            <div class='section'>
                <h2>1. Vertragsparteien</h2>
                <p><strong>Verkäufer:</strong> {$sellerData['name']}<br>
                Adresse: {$sellerData['address']}<br>
                Telefon: {$sellerData['phone']}</p>
                
                <p><strong>Käufer:</strong> {$buyerData['name'] ?? '[Name des Käufers]'}<br>
                Adresse: {$buyerData['address'] ?? '[Adresse des Käufers]'}</p>
            </div>
            
            <div class='section'>
                <h2>2. Fahrzeugdaten</h2>
                <p>Marke/Modell: {$vehicle['make']} {$vehicle['model']}<br>
                Erstzulassung: {$vehicle['year']}<br>
                Fahrzeug-Ident-Nr.: [FIN eintragen]<br>
                Kraftstoff: {$vehicle['fuel_type']}<br>
                Leistung: {$vehicle['power_kw']} kW</p>
            </div>
            
            <div class='section'>
                <h2>3. Kaufpreis</h2>
                <p>Der Kaufpreis beträgt: <strong>" . number_format($price, 2, ',', '.') . " €</strong></p>
            </div>
            
            <div class='section'>
                <h2>4. Unterschriften</h2>
                <div class='signature'>
                    <div>
                        <p>_______________________</p>
                        <p>Verkäufer</p>
                    </div>
                    <div>
                        <p>_______________________</p>
                        <p>Käufer</p>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>";
    
    echo json_encode(['contract_html' => $contract]);
}
?>