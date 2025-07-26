<?php
require_once '../config/init.php';

// POST-Kaufvertrag generieren
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Methode nicht erlaubt']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

// Validierung
$required = ['vehicle_id', 'seller_info', 'buyer_info', 'price'];
foreach ($required as $field) {
    if (!isset($input[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Feld $field fehlt"]);
        exit();
    }
}

try {
    // Fahrzeug abrufen
    $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
    $stmt->execute([(int)$input['vehicle_id']]);
    $vehicle = $stmt->fetch();

    if (!$vehicle) {
        throw new Exception('Fahrzeug nicht gefunden');
    }

    // Kaufvertrag HTML generieren
    $contract_html = generateContractHTML($vehicle, $input);

    // PDF-Generierung (simuliert)
    $contract_id = uniqid('contract_');
    
    // In echter Implementierung würde hier TCPDF oder mPDF genutzt
    $pdf_url = "/tmp/{$contract_id}.pdf";
    
    // HTML-Version speichern
    file_put_contents($pdf_url, $contract_html);

    echo json_encode([
        'success' => true,
        'contract_id' => $contract_id,
        'download_url' => "/download_contract.php?id={$contract_id}",
        'preview_html' => $contract_html
    ]);

} catch (Exception $e) {
    logError('Vertragsgenerierung Fehler', ['error' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode(['error' => 'Vertrag konnte nicht generiert werden']);
}

function generateContractHTML($vehicle, $input) {
    $html = '<!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <title>Kaufvertrag</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 40px; }
            .header { text-align: center; margin-bottom: 40px; }
            .section { margin: 20px 0; }
            .signature { margin-top: 60px; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>KRAFTFAHRZEUG-KAUFVERTRAG</h1>
        </div>
        
        <div class="section">
            <h2>1. Vertragsparteien</h2>
            <p><strong>Verkäufer:</strong> ' . htmlspecialchars($input['seller_info']['name']) . '</p>
            <p>Adresse: ' . htmlspecialchars($input['seller_info']['address']) . '</p>
            <p>Telefon: ' . htmlspecialchars($input['seller_info']['phone']) . '</p>
            
            <p><strong>Käufer:</strong> ' . htmlspecialchars($input['buyer_info']['name']) . '</p>
            <p>Adresse: ' . htmlspecialchars($input['buyer_info']['address']) . '</p>
            <p>Telefon: ' . htmlspecialchars($input['buyer_info']['phone']) . '</p>
        </div>
        
        <div class="section">
            <h2>2. Fahrzeugdaten</h2>
            <table>
                <tr><td>Marke/Modell:</td><td>' . htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']) . '</td></tr>
                <tr><td>Baujahr:</td><td>' . htmlspecialchars($vehicle['year']) . '</td></tr>
                <tr><td>Fahrgestellnummer:</td><td>' . htmlspecialchars($input['vin'] ?? 'wird eingetragen') . '</td></tr>
                <tr><td>Kilometerstand:</td><td>' . htmlspecialchars($input['mileage']) . ' km</td></tr>
                <tr><td>Kaufpreis:</td><td>' . number_format($input['price'], 2, ',', '.') . ' EUR</td></tr>
            </table>
        </div>
        
        <div class="section">
            <h2>3. Zahlungsmodalitäten</h2>
            <p>Der Kaufpreis wird bei Übergabe des Fahrzeugs in bar bezahlt.</p>
        </div>
        
        <div class="section">
            <h2>4. Haftungsausschluss</h2>
            <p>Das Fahrzeug wird in dem vorliegenden Zustand verkauft. Der Verkäufer übernimmt keine Gewährleistung.</p>
        </div>
        
        <div class="signature">
            <table>
                <tr>
                    <td><strong>Ort, Datum</strong></td>
                    <td><strong>Unterschrift Verkäufer</strong></td>
                    <td><strong>Unterschrift Käufer</strong></td>
                </tr>
                <tr>
                    <td>____________________</td>
                    <td>____________________</td>
                    <td>____________________</td>
                </tr>
            </table>
        </div>
    </body>
    </html>';
    
    return $html;
}