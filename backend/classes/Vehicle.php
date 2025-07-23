<?php
/**
 * Carfify Vehicle Management Class
 * 
 * Diese Klasse handhabt alle Fahrzeug-bezogenen Operationen:
 * - Suche nach Fahrzeugen via HSN/TSN
 * - Validierung von Fahrzeug-Kennungen
 * - Caching von Fahrzeugdaten
 * - Verwaltung der KBA (Kraftfahrtbundesamt) Datenbank
 * 
 * @package Carfify
 * @author Carfify Team
 * @version 1.0.0
 */

class Vehicle {
    /** @var PDO|null Datenbankverbindung */
    private ?PDO $db;
    
    /** @var int Cache-Dauer in Sekunden */
    private const CACHE_DURATION = 3600;
    
    /** @var string Cache-Verzeichnis */
    private const CACHE_DIR = __DIR__ . '/../../cache/';
    
    /**
     * Konstruktor
     * @param PDO $db PDO-Datenbankverbindung
     */
    public function __construct(PDO $db) {
        $this->db = $db;
        $this->ensureCacheDirectory();
    }

    /**
     * Sicherstellt, dass Cache-Verzeichnis existiert
     */
    private function ensureCacheDirectory(): void {
        if (!is_dir(self::CACHE_DIR)) {
            mkdir(self::CACHE_DIR, 0755, true);
        }
    }

    /**
     * Validiert HSN/TSN Format
     * @param string $hsn 4-stellige Herstellerschlüssel-Nummer
     * @param string $tsn 3-stellige Typschlüssel-Nummer
     * @return array ['hsn' => ..., 'tsn' => ...] bereinigte Werte
     * @throws Exception Bei ungültiger Eingabe
     */
    public function validateIdentifiers(string $hsn, string $tsn): array {
        $hsn = strtoupper(trim($hsn));
        $tsn = strtoupper(trim($tsn));

        // HSN: 4 Stellen, nur Zahlen
        if (!preg_match('/^\d{4}$/', $hsn)) {
            throw new Exception('HSN muss aus genau 4 Ziffern bestehen (z.B. 0283 für VW)');
        }

        // TSN: 3 Stellen, alphanumerisch
        if (!preg_match('/^[A-Z0-9]{3}$/', $tsn)) {
            throw new Exception('TSN muss aus genau 3 Buchstaben oder Ziffern bestehen (z.B. BFF oder 909)');
        }

        return ['hsn' => $hsn, 'tsn' => $tsn];
    }

    /**
     * Findet Fahrzeug anhand HSN/TSN
     * @param string $hsn Herstellerschlüssel-Nummer
     * @param string $tsn Typschlüssel-Nummer
     * @return array|null Vollständige Fahrzeugdaten
     */
    public function findByHsnTsn(string $hsn, string $tsn): ?array {
        try {
            $validated = $this->validateIdentifiers($hsn, $tsn);
            $hsn = $validated['hsn'];
            $tsn = $validated['tsn'];

            // Cache-Key generieren
            $cacheKey = 'vehicle_' . md5($hsn . $tsn);
            
            // Cache prüfen
            if ($cached = $this->getCache($cacheKey)) {
                return $cached;
            }

            // Datenbankabfrage
            $stmt = $this->db->prepare("
                SELECT 
                    id,
                    hsn,
                    tsn,
                    make AS manufacturer,
                    model,
                    variant,
                    engine,
                    power_kw,
                    power_ps,
                    fuel_type,
                    year_from,
                    year_to
                FROM vehicles 
                WHERE hsn = :hsn 
                AND tsn = :tsn
                ORDER BY year_from DESC
                LIMIT 1
            ");

            $stmt->execute([
                ':hsn' => $hsn,
                ':tsn' => $tsn
            ]);

            $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);

            // Daten transformieren
            if ($vehicle) {
                $vehicle = $this->transformVehicleData($vehicle);
                $this->setCache($cacheKey, $vehicle);
                return $vehicle;
            }

            return null;

        } catch (PDOException $e) {
            error_log("Fahrzeugsuche fehlgeschlagen: " . $e->getMessage());
            throw new Exception('Fahrzeug nicht gefunden');
        }
    }

    /**
     * Transformiert Fahrzeugdaten für Frontend
     * @param array $raw Rudimentäre DB-Daten
     * @return array Aufbereitete Daten
     */
    private function transformVehicleData(array $raw): array {
        return [
            'id' => $raw['id'],
            'manufacturer' => $raw['manufacturer'] ?? 'Unbekannt',
            'model' => $raw['model'] ?? 'Unbekannt',
            'variant' => $raw['variant'] ?? '',
            'engine' => [
                'type' => $raw['engine'] ?? '',
                'power_kw' => (int)($raw['power_kw'] ?? 0),
                'power_ps' => (int)($raw['power_ps'] ?? 0),
                'fuel' => $raw['fuel_type'] ?? 'Unbekannt'
            ],
            'years' => [
                'from' => (int)($raw['year_from'] ?? 0),
                'to' => (int)($raw['year_to'] ?? 0)
            ],
            'identifiers' => [
                'hsn' => $raw['hsn'],
                'tsn' => $raw['tsn']
            ]
        ];
    }

    /**
     * Schnellsuche nach Fahrzeugmodellen
     * @param string $search Suchbegriff (Min. 2 Zeichen)
     * @param int $limit Ergebnislimit (Standard: 20)
     * @return array Suchergebnisse
     */
    public function searchByKeyword(string $search, int $limit = 20): array {
        $search = trim($search);
        
        if (strlen($search) < 2) {
            return [];
        }

        $cacheKey = 'search_' . md5($search) . '_' . $limit;
        
        if ($cached = $this->getCache($cacheKey)) {
            return $cached;
        }

        try {
            $stmt = $this->db->prepare("
                SELECT DISTINCT
                    make,
                    model,
                    variant,
                    fuel_type,
                    year_from,
                    year_to
                FROM vehicles 
                WHERE (make ILIKE :search 
                    OR model ILIKE :search 
                    OR variant ILIKE :search)
                AND year_from >= 1990
                ORDER BY make, model, year_from DESC
                LIMIT :limit
            ");

            $stmt->execute([
                ':search' => '%' . $search . '%',
                ':limit' => $limit
            ]);

            $results = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $results[] = [
                    'display' => $this->formatVehicleDisplayName($row),
                    'raw' => $row
                ];
            }

            $this->setCache($cacheKey, $results);
            return $results;

        } catch (PDOException $e) {
            error_log("Suche fehlgeschlagen: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Formatiert Anzeigenamen für Suchergebnisse
     * @param array $vehicle Rohdaten
     * @return string Formatierte Anzeige
     */
    private function formatVehicleDisplayName(array $vehicle): string {
        $parts = [
            $vehicle['make'],
            $vehicle['model'],
            $vehicle['variant']
        ];
        
        $display = implode(' ', array_filter($parts));
        
        // Kraftstoff hinzufügen
        if (!empty($vehicle['fuel_type'])) {
            $display .= ' (' . $vehicle['fuel_type'] . ')';
        }
        
        // Baujahre hinzufügen
        if ($vehicle['year_from'] || $vehicle['year_to']) {
            $years = $vehicle['year_from'];
            if ($vehicle['year_to'] && $vehicle['year_to'] !== $vehicle['year_from']) {
                $years .= '-' . $vehicle['year_to'];
            }
            $display .= ' - ' . $years;
        }
        
        return $display;
    }

    /**
     * Holt aus dem Cache
     * @param string $key Cache-Key
     * @return mixed|null Gecachte Daten
     */
    private function getCache(string $key) {
        $file = self::CACHE_DIR . $key . '.cache';
        
        if (!file_exists($file)) {
            return null;
        }

        $data = unserialize(file_get_contents($file));
        
        if (!$data || $data['expires'] < time()) {
            unlink($file);
            return null;
        }
        
        return $data['data'];
    }

    /**
     * Speichert im Cache
     * @param string $key Cache-Key
     * @param mixed $data Zu speichernde Daten
     */
    private function setCache(string $key, $data): void {
        $cacheData = [
            'data' => $data,
            'expires' => time() + self::CACHE_DURATION
        ];
        
        file_put_contents(
            self::CACHE_DIR . $key . '.cache',
            serialize($cacheData),
            LOCK_EX
        );
    }

    /**
     * Leert veraltete Cache-Einträge
     * @return int Anzahl gelöschter Dateien
     */
    public function clearExpiredCache(): int {
        $files = glob(self::CACHE_DIR . '*.cache');
        $deleted = 0;
        
        foreach ($files as $file) {
            $data = unserialize(@file_get_contents($file));
            
            if (!$data || $data['expires'] < time()) {
                unlink($file);
                $deleted++;
            }
        }
        
        return $deleted;
    }

    /**
     * Relevante Baujahre für Filter
     * @return array Jahresbereiche
     */
    public function getRelevantYears(): array {
        try {
            $stmt = $this->db->query("
                SELECT 
                    MIN(year_from) as min_year,
                    MAX(GREATEST(year_from, year_to)) as max_year
                FROM vehicles
                WHERE year_from >= 1990
            ");
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return ['min_year' => 1990, 'max_year' => date('Y')];
        }
    }

    /**
     * Vorschläge für HSN/TSN Kombinationen
     * @param string $partial Partieller Suchbegriff
     * @param int $limit Max. Ergebnisse
     * @return array Liste passender Kombinationen
     */
    public function getIdentifierSuggestions(string $partial, int $limit = 10): array {
        $partial = strtoupper(trim($partial));
        
        if (strlen($partial) < 2) {
            return [];
        }

        try {
            $stmt = $this->db->prepare("
                SELECT DISTINCT
                    hsn,
                    tsn,
                    make,
                    model
                FROM vehicles
                WHERE hsn LIKE :partial OR tsn LIKE :partial
                ORDER BY make, model
                LIMIT :limit
            ");

            $stmt->execute([
                ':partial' => $partial . '%',
                ':limit' => $limit
            ]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            return [];
        }
    }
}
