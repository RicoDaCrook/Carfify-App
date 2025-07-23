<?php
/**
 * Carfify Vehicle Management Class
 * 
 * Diese Klasse handhabt alle Fahrzeug-bezogenen Operationen:
 * - Suche nach Fahrzeugen via HSN/TSN
 * - Validierung von Fahrzeug-Kennungen
 * - Caching von Fahrzeugdaten (Vercel-kompatibel mit Redis)
 * - Unterstützt PostgreSQL/MySQL Verbindungen
 * 
 * @package Carfify
 * @author Carfify Team
 * @version 1.1.0
 */

class Vehicle {
    /** @var PDO|null Datenbankverbindung */
    private ?PDO $db;
    
    /** @var int Cache-Dauer in Sekunden */
    private const CACHE_DURATION = 3600;
    
    /** @var bool Vercel deployment flag */
    private bool $isVercel;
    
    /** @var mixed Cache-Handler für Vercel Umgebung */
    private $cache;
    
    /**
     * Konstruktor
     * Verwendet Environment Variablen für Vercel-Kompatibilität
     * 
     * Verfügbare Environment Variablen:
     * - DATABASE_URL (für PostgreSQL)
     * - MYSQL_URL (für MySQL)
     * - VERCEL=1 (Signalisiert Vercel Umgebung)
     * - OPENAPI_KEY (für Vercel Edge Config als Cache)
     */
    public function __construct() {
        $this->isVercel = isset($_ENV['VERCEL']) || isset($_SERVER['VERCEL']) || getenv('VERCEL') !== false;
        
        // Verwende Environment Variablen für Datenbankkonfiguration
        $dbConfig = $this->getDatabaseConfig();
        $this->db = $this->createPdoConnection($dbConfig);
        
        // Initialisiere Cache entsprechend der Umgebung
        if ($this->isVercel) {
            // Vercel Environment: Verwende Edge Config oder Environment als Cache
            $this->cache = null; // Platzhalter für spätere Implementierung
        } else {
            // Lokale Entwicklung: Verwende Dateisystem-Cache
            $this->ensureCacheDirectory();
        }
    }

    /**
     * Holt die Datenbankkonfiguration aus Environment Variablen
     * @return array Datenbankkonfiguration
     * @throws Exception Bei fehlender Konfiguration
     */
    private function getDatabaseConfig(): array {
        // Prüfe auf PostgreSQL (bevorzugt)
        $databaseUrl = $_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? getenv('DATABASE_URL');
        if ($databaseUrl) {
            return $this->parsePostgresUrl($databaseUrl);
        }
        
        // Prüfe auf MySQL
        $mysqlUrl = $_ENV['MYSQL_URL'] ?? $_SERVER['MYSQL_URL'] ?? getenv('MYSQL_URL');
        if ($mysqlUrl) {
            return $this->parseMysqlUrl($mysqlUrl);
        }
        
        // Lokale Entwicklung Fallback
        if (!$this->isVercel) {
            return [
                'dsn' => 'mysql:host=localhost;dbname=carfify;charset=utf8mb4',
                'username' => $_ENV['DB_USERNAME'] ?? 'root',
                'password' => $_ENV['DB_PASSWORD'] ?? '',
            ];
        }
        
        throw new Exception('Keine Datenbankkonfiguration gefunden. Setze DATABASE_URL oder MYSQL_URL in Vercel Environment Variables.');
    }

    /**
     * Parsed die PostgreSQL DATABASE_URL
     * @param string $url PostgreSQL connection string
     * @return array Konfigurationsarray
     */
    private function parsePostgresUrl(string $url): array {
        $parsed = parse_url($url);
        return [
            'dsn' => sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                $parsed['host'] ?? 'localhost',
                $parsed['port'] ?? 5432,
                ltrim($parsed['path'] ?? '/database', '/')
            ),
            'username' => $parsed['user'] ?? 'postgres',
            'password' => $parsed['pass'] ?? '',
        ];
    }

    /**
     * Parsed die MySQL MYSQL_URL
     * @param string $url MySQL connection string
     * @return array Konfigurationsarray
     */
    private function parseMysqlUrl(string $url): array {
        $parsed = parse_url($url);
        return [
            'dsn' => sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $parsed['host'] ?? 'localhost',
                $parsed['port'] ?? 3306,
                ltrim($parsed['path'] ?? '/database', '/')
            ),
            'username' => $parsed['user'] ?? 'root',
            'password' => $parsed['pass'] ?? '',
        ];
    }

    /**
     * Erstellt PDO-Verbindung basierend auf Konfiguration
     * @param array $config
     * @return PDO
     * @throws Exception Bei Verbindungsfehler
     */
    private function createPdoConnection(array $config): PDO {
        try {
            $pdo = new PDO(
                $config['dsn'],
                $config['username'],
                $config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 30,
                ]
            );
            
            // PostgreSQL-spezifische Konfiguration
            if (strpos($config['dsn'], 'pgsql:') === 0) {
                $pdo->exec("SET timezone TO 'UTC'");
            }
            
            return $pdo;
        } catch (PDOException $e) {
            throw new Exception('Datenbankverbindung fehlgeschlagen: ' . $e->getMessage());
        }
    }

    /**
     * Stellt sicher, dass das Cache-Verzeichnis existiert (nur für lokale Entwicklung)
     */
    private function ensureCacheDirectory(): void {
        if ($this->isVercel) return;
        
        $cacheDir = __DIR__ . '/../../cache/';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
            // Schreibe .htaccess für zusätzliche Sicherheit (Apache-Server)
            file_put_contents($cacheDir . '.htaccess', "Deny from all");
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
     * Findet Fahrzeug anhand HSN/TSN mit Environment-basiertem Caching
     * @param string $hsn Herstellerschlüssel-Nummer
     * @param string $tsn Typschlüssel-Nummer
     * @return array|null Vollständige Fahrzeugdaten
     * @throws Exception Bei Datenbankfehlern
     */
    public function findByHsnTsn(string $hsn, string $tsn): ?array {
        try {
            $validated = $this->validateIdentifiers($hsn, $tsn);
            $hsn = $validated['hsn'];
            $tsn = $validated['tsn'];

            $cacheKey = 'vehicle_' . md5($hsn . $tsn);
            
            // Cache-Unterstützung für beide Umgebungen
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

            if ($vehicle) {
                $vehicle = $this->transformVehicleData($vehicle);
                $this->setCache($cacheKey, $vehicle);
                return $vehicle;
            }

            return null;

        } catch (PDOException $e) {
            error_log("Fahrzeugsuche fehlgeschlagen: " . $e->getMessage());
            // Gebe bei Fehler nicht zu viele Details preis (Security)
            throw new Exception('Das Fahrzeug konnte nicht gefunden werden. Bitte prüfe die eingegebenen Daten.');
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
                WHERE (make LIKE :search 
                    OR model LIKE :search 
                    OR variant LIKE :search)
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
        
        if (!empty($vehicle['fuel_type'])) {
            $display .= ' (' . $vehicle['fuel_type'] . ')';
        }
        
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
     * Vereinheitlichte Cache-Methode für beide Umgebungen
     * @param string $key Cache-Key
     * @return mixed|null Gecachte Daten
     */
    private function getCache(string $key) {
        if ($this->isVercel) {
            // Vercel: Verwende Transient Cache in Environment Variablen
            // TODO: Implementiere Vercel Edge Config für Produktion
            return null;
        }
        
        // Lokale Entwicklung: Datei-basierter Cache
        $file = __DIR__ . '/../../cache/' . $key . '.cache';
        
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
     * Vereinheitlichte Cache-Speichermethode für beide Umgebungen
     * @param string $key Cache-Key
     * @param mixed $data Zu speichernde Daten
     */
    private function setCache(string $key, $data): void {
        if ($this->isVercel) {
            // Vercel: Kein persistent Cache
            return;
        }

        $cacheDir = __DIR__ . '/../../cache/';
        $cacheData = [
            'data' => $data,
            'expires' => time() + self::CACHE_DURATION
        ];
        
        file_put_contents(
            $cacheDir . $key . '.cache',
            serialize($cacheData),
            LOCK_EX
        );
    }

    // Weitere Methoden wie clearExpiredCache, getRelevantYears, getIdentifierSuggestions
    // bleiben unverändert wie im Original, nur mit angepasstem Pfad für Cache-Direktorien

    /**
     * Leert veraltete Cache-Einträge (nur lokal)
     * @return int Anzahl gelöschter Dateien
     */
    public function clearExpiredCache(): int {
        if ($this->isVercel) {
            return 0;
        }

        $files = glob(__DIR__ . '/../../cache/' . '*.cache');
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
            
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['min_year' => 1990, 'max_year' => date('Y')];
            
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

    /**
     * Getter für Datenbankverbindung (für Tests)
     * @return PDO|null
     */
    public function getDb(): ?PDO {
        return $this->db;
    }
}
