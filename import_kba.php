<?php
/**
 * import_kba.php
 *
 * Professional KBA (HSN/TSN) Datenimport-Script
 *
 * Lädt die aktuellen KBA-Schlüssel vom KBA herunter,
 * parst die Daten und speichert sie in eine PostgreSQL-Datenbank.
 *
 * Usage: php import_kba.php [--force-update] [--dry-run] [--help]
 * Web-Aufruf: https://<domain>/backend/import_kba.php?token=<secure_token>
 *
 * @author Carfify Team
 * @version 1.1.0
 */

if (!defined('STDIN') && !in_array(php_sapi_name(), ['cli', 'cli-server'])) {
    $secureToken = 'carfify_kba_secure_2024_' . date('Y-m');
    if (!isset($_GET['token']) || $_GET['token'] !== $secureToken) {
        http_response_code(403);
        exit('Access Denied – Invalid Token');
    }
    error_log("[KBA Import] Web access initiated at " . date('Y-m-d H:i:s'));
}

set_time_limit(300);
ini_set('memory_limit', '512M');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Database.php';

class KBAImporter {
    private $db;
    private $config;
    private $stats = [
        'total_rows'    => 0,
        'new_entries'   => 0,
        'updated_entries' => 0,
        'skipped_entries' => 0,
        'errors'        => 0,
    ];

    public function __construct() {
        $this->config = require __DIR__ . '/config/database.php';
        $this->db     = new Database($this->config['database']);
        $this->initializeDatabase();
    }

    private function initializeDatabase(): void {
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

        CREATE INDEX IF NOT EXISTS idx_vehicles_hsn  ON vehicles(hsn);
        CREATE INDEX IF NOT EXISTS idx_vehicles_tsn  ON vehicles(tsn);
        CREATE INDEX IF NOT EXISTS idx_vehicles_make ON vehicles(make);
        CREATE INDEX IF NOT EXISTS idx_vehicles_model ON vehicles(model);
        ";

        $this->db->exec($sql);
        $this->logMessage('Database initialized successfully');
    }

    private function downloadKBAData(): string {
        $kbaUrl  = 'https://www.kba.de/SharedDocs/Downloads/DE/Statistik/Fahrzeuge/FZ/kennzeichen/kba_stand_basisdaten_csv.csv?__blob=publicationFile&v=11';
        $tmpFile = sys_get_temp_dir() . '/kba_' . date('Y-m-d') . '.csv';

        $ctx = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'timeout' => 30,
                'header'  => "User-Agent: Carfify-KBA/1.1\r\n",
            ],
        ]);

        $csv = file_get_contents($kbaUrl, false, $ctx);
        if ($csv === false) {
            throw new RuntimeException("KBA download failed");
        }

        file_put_contents($tmpFile, mb_convert_encoding($csv, 'UTF-8', 'ISO-8859-1'));
        $this->logMessage("KBA data downloaded: $tmpFile");

        return $tmpFile;
    }

    private function parseCSV(string $csvFile): array {
        $fh       = fopen($csvFile, 'r');
        $vehicles = $processed = [];

        if (!$fh) {
            throw new RuntimeException("Cannot open $csvFile");
        }

        $headers = array_map(function ($h) {
            $h = strtolower(trim($h));
            $h = str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $h);
            return preg_replace('/[^a-z0-9_]/', '_', $h);
        }, fgetcsv($fh, 0, ';'));

        while (($row = fgetcsv($fh, 0, ';')) !== false) {
            $this->stats['total_rows']++;
            if (count($row) < count($headers)) {
                continue;
            }

            $v      = array_combine($headers, $row);
            $hsn    = str_pad(trim($v['hsn']), 4, '0', STR_PAD_LEFT);
            $tsn    = str_pad(trim($v['tsn']), 3, '0', STR_PAD_LEFT);
            $key    = "$hsn-$tsn";

            if (isset($processed[$key])) {
                continue;
            }
            $processed[$key] = true;

            $vehicles[] = [
                'hsn'       => $hsn,
                'tsn'       => $tsn,
                'make'      => $this->extractManufacturer(substr($tsn, 0, 1)),
                'model'     => trim($v['handelsbezeichung'] ?? ''),
                'variant'   => trim($v['typ'] ?? ''),
                'engine'    => trim($v['motor'] ?? ''),
                'power_kw'  => (int)($v['kw'] ?? 0),
                'power_ps'  => (int)($v['ps1'] ?? 0),
                'fuel_type' => $this->normalizeFuelType($v['kraftstoffart']),
                'year_from' => (int)($v['von_erstm'] ?? 0),
                'year_to'   => (int)($v['bis_erstm'] ?? 0),
            ];
        }
        fclose($fh);

        $this->logMessage('Parsed ' . count($vehicles) . ' unique vehicles');
        return $vehicles;
    }

    private function extractManufacturer(string $code): string {
        $map = [
            '0' => 'Sonstige',
            '1' => 'VW',
            '2' => 'Opel',
            '3' => 'Ford',
            '4' => 'Mercedes-Benz',
            '5' => 'BMW',
            '6' => 'Audi',
            '7' => 'Porsche',
            '8' => 'Nissan',
            '9' => 'Renault',
        ];
        return $map[$code] ?? 'Sonstige';
    }

    private function normalizeFuelType(string $fuel): string {
        $fuel = strtolower(trim($fuel));
        foreach ([
            'diesel' => 'Diesel',
            'otto'   => 'Benzin',
            'unbleif'=> 'Benzin',
            'gas'    => 'Gas',
            'elektro'=> 'Elektro',
            'hybrid' => 'Hybrid',
            'ethanol'=> 'Ethanol',
        ] as $needle => $type) {
            if (str_contains($fuel, $needle)) {
                return $type;
            }
        }
        return 'Unbekannt';
    }

    private function importVehicles(array $vehicles): void {
        $sql = "
        INSERT INTO vehicles (
            hsn,tsn,make,model,variant,engine,power_kw,power_ps,fuel_type,year_from,year_to
        ) VALUES (
            :hsn,:tsn,:make,:model,:variant,:engine,:power_kw,:power_ps,:fuel_type,:year_from,:year_to
        ) ON CONFLICT (hsn,tsn) DO UPDATE SET
            make       = EXCLUDED.make,
            model      = EXCLUDED.model,
            variant    = EXCLUDED.variant,
            engine     = EXCLUDED.engine,
            power_kw   = EXCLUDED.power_kw,
            power_ps   = EXCLUDED.power_ps,
            fuel_type  = EXCLUDED.fuel_type,
            year_from  = EXCLUDED.year_from,
            year_to    = EXCLUDED.year_to,
            updated_at = CURRENT_TIMESTAMP
        ";

        $stmt = $this->db->prepare($sql);
        foreach ($vehicles as $i => $v) {
            try {
                $stmt->execute([
                    ':hsn'       => $v['hsn'],
                    ':tsn'       => $v['tsn'],
                    ':make'      => $v['make'],
                    ':model'     => $v['model'],
                    ':variant'   => $v['variant'],
                    ':engine'    => $v['engine'],
                    ':power_kw'  => $v['power_kw'],
                    ':power_ps'  => $v['power_ps'],
                    ':fuel_type' => $v['fuel_type'],
                    ':year_from' => $v['year_from'],
                    ':year_to'   => $v['year_to'],
                ]);

                if ($stmt->rowCount() > 0) {
                    $this->stats['new_entries']++;
                } else {
                    $this->stats['updated_entries']++;
                }

                if (($i + 1) % 1000 === 0) {
                    $this->logMessage('Processed ' . ($i + 1) . ' vehicles');
                }
            } catch (Throwable $e) {
                $this->stats['errors']++;
                $this->logError("Row error: " . $e->getMessage());
            }
        }
    }

    private function shouldUpdate(bool $force = false): bool {
        if ($force) return true;

        $last = $this->db->query("
            SELECT MAX(updated_at) FROM vehicles
        ")->fetchColumn();

        return !$last || $last < date('Y-m-d H:i:s', strtotime('-3 months'));
    }

    public function run(bool $force = false, bool $dry = false): array {
        try {
            if (!$this->shouldUpdate($force)) {
                return ['status' => 'up_to_date', 'message' => 'No new data'];
            }

            $csv      = $this->downloadKBAData();
            $vehicles = $this->parseCSV($csv);

            if (!$dry) {
                $this->importVehicles($vehicles);
            }

            @unlink($csv);

            $this->logMessage('Import finished: ' . json_encode($this->stats));
            return [
                'status'    => 'success',
                'stats'     => $this->stats,
                'updated_at'=> date('c'),
            ];
        } catch (Throwable $e) {
            $this->logError('Import failed: ' . $e->getMessage());
            return ['status'=>'error','message'=>$e->getMessage()];
        }
    }

    private function logMessage(string $msg): void {
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
        if (php_sapi_name() === 'cli') echo $line;
        @file_put_contents(__DIR__ . '/logs/kba_import.log', $line, FILE_APPEND | LOCK_EX);
    }
}

/* ---------- CLI ---------- */
if (php_sapi_name() === 'cli') {
    $opt = getopt('', ['force-update', 'dry-run', 'help']);
    if (isset($opt['help'])) {
        echo "Carfify KBA Import CLI\n";
        echo "Usage: php import_kba.php [--force-update] [--dry-run]\n";
        exit;
    }
    $exit = (new KBAImporter())->run(
        isset($opt['force-update']),
        isset($opt['dry-run'])
    );
    exit(($exit['status'] === 'error') ? 1 : 0);
}

/* ---------- Web ---------- */
if (!defined('STDIN')) {
    header('Content-Type: application/json');
    echo json_encode((new KBAImporter())->run(
        !empty($_GET['force']),
        !empty($_GET['dry'])
    ));
}
