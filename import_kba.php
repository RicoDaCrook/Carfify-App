<?php
/**
 * import_kba.php
 * 
 * Professional KBA (HSN/TSN) Datenimport-Script
 * 
 * Lädt die aktuellen KBA-Schlüssel vom KBA herunter,
 * parst die Daten und speichert sie in eine PostgreSQL-Datenbank.
 * Prüft auf Duplikate und bietet einen vierteljährlichen Update-Mechanismus.
 * 
 * Usage: php import_kba.php [--force-update] [--dry-run] [--help]
 * Web-Aufruf per: <webserver>/backend/import_kba.php?token=<secure_token>
 * 
 * @author Carfify Team
 * @version 1.0.0
 */

// Security Check - Nur CLI oder mit gültigem Token aufrufbar
if (php_sapi_name() !== 'cli') {
    $secureToken = 'carfify_kba_secure_2024_' . date('Y-m');
    
    if (!isset($_GET['token']) || $_GET['token'] !== $secureToken) {
        http_response_code(403);
        die('Access Denied - Invalid Token');
    }
    
    // Log web execution
    error_log("[KBA Import] Web access initiated at " . date('Y-m-d H:i:s'));
}

// Config und Settings
set_time_limit(300); // 5 Minuten Max Execution Time
ini_set('memory_limit', '512M');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Database.php';

/**
 * KBA Import Manager
 */
class KBAImporter {
    private $db;
    private $config;
    private $stats = [
        'total_rows' => 0,
        'new_entries' => 0,
        'updated_entries' => 0,
        'skipped_entries' => 0,
        'errors' => 0
    ];
    
    public function __construct() {
        $this->config = require __DIR__ . '/config/database.php';
        $this->db = new Database($this->config['database']);
        
        $this->initializeDatabase();
    }
    
    /**
     * Initialisiert die Datenbank-Tabelle, falls nicht existiert
     */
    private function initializeDatabase() {
        $sql = "
        CREATE TABLE IF NOT EXISTS vehicles (
            id SERIAL PRIMARY KEY,
            hsn VARCHAR(4) NOT NULL,
            tsn VARCHAR(3) NOT NULL,
            make VARCHAR(100) NOT NULL,
            model VARCHAR(150) NOT NULL,
            variant VARCHAR(200),
            engine VARCHAR(100),
            power_kw INTEGER,
            power_ps INTEGER,
            fuel_type VARCHAR(50),
            year_from INTEGER,
            year_to INTEGER,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(hsn, tsn)
        );
        
        CREATE INDEX IF NOT EXISTS idx_vehicles_hsn ON vehicles(hsn);
        CREATE INDEX IF NOT EXISTS idx_vehicles_tsn ON vehicles(tsn);
        CREATE INDEX IF NOT EXISTS idx_vehicles_make ON vehicles(make);
        CREATE INDEX IF NOT EXISTS idx_vehicles_model ON vehicles(model);
        ";
        
        try {
            $this->db->exec($sql);
            $this->logMessage("Database initialized successfully");
        } catch (Exception $e) {
            $this->logError("Database initialization failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Downloader für KBA-Daten
     */
    private function downloadKBAData() {
        $kbaUrl = 'https://www.kba.de/SharedDocs/Downloads/DE/Statistik/Fahrzeuge/FZ/kennzeichen/kba_stand_basisdaten_csv.csv?__blob=publicationFile&v=11';
        $tempFile = sys_get_temp_dir() . '/kba_data_' . date('Y-m-d') . '.csv';
        
        $this->logMessage("Downloading KBA data from: $kbaUrl");
        
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 30,
                'user_agent' => 'Carfify KBA Importer 1.0'
            ]
        ]);
        
        $data = file_get_contents($kbaUrl, false, $context);
        
        if ($data === false) {
            throw new Exception("Failed to download KBA data from $kbaUrl");
        }
        
        // Speichere temporär als UTF-8 konvertiert
        $utf8Data = mb_convert_encoding($data, 'UTF-8', 'ISO-8859-1');
        file_put_contents($tempFile, $utf8Data);
        
        $this->logMessage("KBA data downloaded and saved to: $tempFile");
        return $tempFile;
    }
    
    /**
     * Parst die CSV-Datei und bereitet Daten vor
     */
    private function parseCSV($csvFile) {
        $handle = fopen($csvFile, 'r');
        
        if (!$handle) {
            throw new Exception("Cannot open CSV file: $csvFile");
        }
        
        $vehicles = [];
        $headers = [];
        
        // Lese Header-Zeile
        $rawHeaders = fgetcsv($handle, 0, ';');
        if (!$rawHeaders) {
            throw new Exception("Cannot read CSV headers");
        }
        
        // Header normalisieren
        $headers = array_map(function($h) {
            $h = strtolower(trim($h));
            $h = str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $h);
            $h = preg_replace('/[^a-z0-9_]/', '_', $h);
            return $h;
        }, $rawHeaders);
        
        // Cache für bereits verarbeitete HSN/TSN Kombinationen
        $processed = [];
        
        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            $this->stats['total_rows']++;
            
            if (count($row) < count($headers)) {
                $this->logMessage("Skipping incomplete row #{$this->stats['total_rows']}");
                continue;
            }
            
            $vehicle = array_combine($headers, $row);
            
            // Extrahiere und validiere Daten
            $hsn = str_pad(trim($vehicle['hsn']), 4, '0', STR_PAD_LEFT);
            $tsn = str_pad(trim($vehicle['tsn']), 3, '0', STR_PAD_LEFT);
            
            // Prüfe auf Duplikat in diesem Durchlauf
            $key = "$hsn-$tsn";
            if (isset($processed[$key])) {
                continue;
            }
            $processed[$key] = true;
            
            // Extrahiere Hersteller (letzte 3 Stellen TSN)
            $manufacturerCode = substr($tsn, 0, 1);
            $make = $this->extractManufacturer($manufacturerCode);
            
            $vehicles[] = [
                'hsn' => $hsn,
                'tsn' => $tsn,
                'make' => $make,
                'model' => trim($vehicle['handelsbezeichung']),
                'variant' => trim($vehicle['typ']),
                'engine' => trim($vehicle['motor'] ?? ''),
                'power_kw' => (int)($vehicle['kw'] ?? 0),
                'power_ps' => (int)($vehicle['ps1'] ?? 0),
                'fuel_type' => $this->normalizeFuelType($vehicle['kraftstoffart']),
                'year_from' => (int)($vehicle['von_erstm'] ?? 0),
                'year_to' => (int)($vehicle['bis_erstm'] ?? 0)
            ];
        }
        
        fclose($handle);
        
        $this->logMessage("Parsed " . count($vehicles) . " unique vehicles from CSV");
        return $vehicles;
    }
    
    /**
     * Extrahiert Hersteller aus TSN
     */
    private function extractManufacturer($code) {
        $manufacturers = [
            '0' => 'Sonstige',
            '1' => 'VW',
            '2' => 'Opel',
            '3' => 'Ford',
            '4' => 'Mercedes-Benz',
            '5' => 'BMW',
            '6' => 'Audi',
            '7' => 'Porsche',
            '8' => 'Nissan',
            '9' => 'Renault'
            // Vollständige Liste könnte aus Datenbank oder Config geladen werden
        ];
        
        return $manufacturers[$code] ?? 'Sonstige';
    }
    
    /**
     * Normalisiert Kraftstoff-Typ
     */
    private function normalizeFuelType($fuel) {
        $fuel = strtolower(trim($fuel));
        
        $mapping = [
            'diesel' => 'Diesel',
            'otto' => 'Benzin',
            'unbleif' => 'Benzin',
            'gas' => 'Gas',
            'elektro' => 'Elektro',
            'hybrid' => 'Hybrid',
            'ethanol' => 'Ethanol'
        ];
        
        foreach ($mapping as $key => $value) {
            if (strpos($fuel, $key) !== false) {
                return $value;
            }
        }
        
        return 'Unbekannt';
    }
    
    /**
     * Importiert Fahrzeugdaten in die Datenbank
     */
    private function importVehicles($vehicles) {
        $this->logMessage("Starting vehicle import...");
        
        $stmt = $this->db->prepare("
            INSERT INTO vehicles (
                hsn, tsn, make, model, variant, engine, 
                power_kw, power_ps, fuel_type, year_from, year_to
            ) VALUES (
                :hsn, :tsn, :make, :model, :variant, :engine,
                :power_kw, :power_ps, :fuel_type, :year_from, :year_to
            )
            ON CONFLICT (hsn, tsn) DO UPDATE SET
                make = EXCLUDED.make,
                model = EXCLUDED.model,
                variant = EXCLUDED.variant,
                engine = EXCLUDED.engine,
                power_kw = EXCLUDED.power_kw,
                power_ps = EXCLUDED.power_ps,
                fuel_type = EXCLUDED.fuel_type,
                year_from = EXCLUDED.year_from,
                year_to = EXCLUDED.year_to,
                updated_at = CURRENT_TIMESTAMP
        ");
        
        foreach ($vehicles as $idx => $vehicle) {
            try {
                $result = $stmt->execute([
                    ':hsn' => $vehicle['hsn'],
                    ':tsn' => $vehicle['tsn'],
                    ':make' => $vehicle['make'],
                    ':model' => $vehicle['model'],
                    ':variant' => $vehicle['variant'],
                    ':engine' => $vehicle['engine'],
                    ':power_kw' => $vehicle['power_kw'],
                    ':power_ps' => $vehicle['power_ps'],
                    ':fuel_type' => $vehicle['fuel_type'],
                    ':year_from' => $vehicle['year_from'],
                    ':year_to' => $vehicle['year_to']
                ]);
                
                if ($result && $stmt->rowCount() > 0) {
                    $this->stats['new_entries']++;
                } else {
                    $this->stats['updated_entries']++;
                }
                
                if (($idx + 1) % 1000 === 0) {
                    $this->logMessage("Processed " . ($idx + 1) . " vehicles...");
                }
                
            } catch (Exception $e) {
                $this->stats['errors']++;
                $this->logError("Error processing {$vehicle['hsn']} - {$vehicle['tsn']}: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Prüft, ob ein Update notwendig ist
     */
    private function shouldUpdate($force = false) {
        if ($force) {
            return true;
        }
        
        // Prüfe letztes Update
        $stmt = $this->db->prepare("
            SELECT MAX(updated_at) as last_update 
            FROM vehicles
        ");
        $result = $stmt->execute();
        $lastUpdate = $result->fetchColumn();
        
        if (!$lastUpdate) {
            return true; // Erster Import
        }
        
        // Letztes Update vor 3 Monaten?
        $threeMonthsAgo = date('Y-m-d H:i:s', strtotime('-3 months'));
        
        return $lastUpdate < $threeMonthsAgo;
    }
    
    /**
     * Führt den kompletten Import durch
     */
    public function run($force = false, $dryRun = false) {
        try {
            // Prüfe Update-Bedarf
            if (!$this->shouldUpdate($force)) {
                $this->logMessage("No update needed. Last update within 3 months.");
                return [
                    'status' => 'up_to_date',
                    'message' => 'Database is up to date'
                ];
            }
            
            $this->logMessage("Starting KBA import process...");
            
            // Daten herunterladen
            $csvFile = $this->downloadKBAData();
            
            // Daten parsen
            $vehicles = $this->parseCSV($csvFile);
            
            if (!$dryRun) {
                // Daten importieren
                $this->importVehicles($vehicles);
            }
            
            // Aufräumen
            if (file_exists($csvFile)) {
                unlink($csvFile);
            }
            
            // Statistik ausgeben
            $this->logMessage("Import completed: " . print_r($this->stats, true));
            
            return [
                'status' => 'success',
                'stats' => $this->stats,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            $this->logError("Import failed: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Logging-Methoden
     */
    private function logMessage($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] $message\n";
        
        // CLI Output
        if (php_sapi_name() === 'cli') {
            echo $logEntry;
        }
        
        // File logging
        file_put_contents(
            __DIR__ . '/logs/kba_import.log',
            $logEntry,
            FILE_APPEND | LOCK_EX
        );
    }
    
    private function logError($message) {
        $this->logMessage("ERROR: $message");
    }
}

// CLI-Konfiguration
if (php_sapi_name() === 'cli') {
    $options = getopt('', ['force-update', 'dry-run', 'help']);
    
    if (isset($options['help'])) {
        echo "Carfify KBA Import Tool\n";
        echo "Usage: php import_kba.php [--force-update] [--dry-run] [--help]\n";
        exit;
    }
    
    $runner = new KBAImporter();
    $result = $runner->run(
        isset($options['force-update']),
        isset($options['dry-run'])
    );
    
    if ($result['status'] === 'error') {
        exit(1);
    }
    
    exit(0);
}

// Web-Aufruf
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
    
    $runner = new K