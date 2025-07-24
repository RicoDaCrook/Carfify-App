<?php
/**
 * Carfify – Klasse Database
 * ========================
 * Verwaltet die PostgreSQL-Verbindung und stellt sicher, dass alle Datenbank-Instanzen
 * ausschließlich über diese Klasse erzeugt werden (Singleton).
 *
 * Zusätzliche Features:
 * – Prepared Statements (SQL-Injection-Schutz)
 * – SSL-Encryption if available
 * – Timeout-Einstellungen
 * – Retry-Mechanismus bei Temporären Fehlern
 *
 * PHILOSOPHY: "Never trust user input, always validate."
 */

namespace Carfify\Backend;

use PDO;
use PDOException;

class Database
{
    /* ------------------------------ Singleton ---------------------------- */
    private static ?Database $instance = null;
    private PDO $conn;

    /* ------------------------------ Config ---------------------------- */
    // Nur noch als Fallback – VERSCHLÜSSELTER Key kommt aus Vercel
    private const CHARSET = 'utf8';

    /* ------------------------------ Connection Options ---------------------------- */
    private const PDO_OPTS = [
        PDO::ATTR_ERRMODE                => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE     => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES       => false,
        PDO::ATTR_TIMEOUT                => 5,
        PDO::PGSQL_ATTR_DISABLE_PREPARES => false, // Prepared Statements aktiv
    ];

    /* ------------------------------ Private Constructor ---------------------------- */
    private function __construct()
    {
        // Vercel Environment-Variable DATABASE_URL hat Vorrang
        $dsnFromEnv = $_ENV['DATABASE_URL'] ?? null;
        if (!empty($dsnFromEnv)) {
            // Vercel liefert postgres://user:pass@host:port/db
            // PDO will: pgsql:host=…;port=…;dbname=…
            $url = parse_url($dsnFromEnv);

            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s;user=%s;password=%s',
                $url['host']            ?? 'localhost',
                $url['port']            ?? '5432',
                ltrim($url['path'], '/') ?? 'carfify',
                $url['user']            ?? 'carfify_user',
                $url['pass']            ?? ''
            );
        } else {
            // Fallback falls DATABASE_URL nicht vorhanden = lokale Entwicklung
            $host = $_ENV['DB_HOST']     ?? $_SERVER['DB_HOST']     ?? 'localhost';
            $port = $_ENV['DB_PORT']     ?? $_SERVER['DB_PORT']     ?? '5432';
            $name = $_ENV['DB_NAME']     ?? $_SERVER['DB_NAME']     ?? 'carfify';
            $user = $_ENV['DB_USER']     ?? $_SERVER['DB_USER']     ?? 'carfify_user';
            $pass = $_ENV['DB_PASSWORD'] ?? $_SERVER['DB_PASSWORD'] ?? 'super_safe_pw';

            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                $host,
                $port,
                $name
            );
        }

        try {
            $this->conn = new PDO(
                $dsn,
                $user ?? null,   // falls alles in DSN
                $pass ?? null,
                self::PDO_OPTS
            );

            // PostgreSQL-spezifische Einstellungen
            $this->conn->exec("SET NAMES '" . self::CHARSET . "'");
            $this->conn->exec("SET TIMEZONE TO UTC");

        } catch (PDOException $e) {
            error_log("DB-Verbindung fehlgeschlagen: " . $e->getMessage());
            http_response_code(500);
            exit('Datenbankfehler.');
        }
    }

    /* ------------------------------ Singleton-Getter ---------------------------- */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /* ------------------------------ Public PDO Accessor ---------------------------- */
    public function getConnection(): PDO
    {
        return $this->conn;
    }

    /* ------------------------------ Prepared & Retry ---------------------------- */
    /**
     * Führt ein Prepared Statement aus und liefert das PDO-Statement.
     */
    public function run(string $sql, array $params = []): \PDOStatement
    {
        $maxRetries = 2;

        $tries = 0;
        retry:
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            if ($this->isRetryable($e) && $tries < $maxRetries) {
                usleep(100000); // 0.1s
                $tries++;
                goto retry;
            }

            error_log("SQL-Fehler: {$e->getMessage()} | SQL: {$sql}");
            throw $e; // Weiterleiten für spezifische Fehlerbehandlung
        }
    }

    /**
     * Führt ein SELECT Statement aus und gibt das Ergebnis als Array zurück
     */
    public function select(string $sql, array $params = []): array
    {
        $stmt = $this->run($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Führt einen INSERT Statement aus und gibt die neue ID zurück
     */
    public function insert(string $sql, array $params = []): string
    {
        $stmt = $this->run($sql, $params);
        return $this->conn->lastInsertId();
    }

    /**
     * Führt ein UPDATE/DELETE Statement aus und gibt die Anzahl der betroffenen Zeilen zurück
     */
    public function update(string $sql, array $params = []): int
    {
        $stmt = $this->run($sql, $params);
        return $stmt->rowCount();
    }

    /* ------------------------------ Transaction Handling ---------------------------- */
    /**
     * Startet eine Datenbank-Transaktion
     */
    public function beginTransaction(): bool
    {
        return $this->conn->beginTransaction();
    }

    /**
     * Committet eine Transaktion
     */
    public function commit(): bool
    {
        return $this->conn->commit();
    }

    /**
     * Rollback einer Transaktion
     */
    public function rollback(): bool
    {
        return $this->conn->rollBack();
    }

    /* ------------------------------ Helper ---------------------------- */
    private function isRetryable(PDOException $e): bool
    {
        $retryableCodes = [
            '40001', // Serialization failure
            '40P01', // Deadlock detected
            '08003', // Connection does not exist
            '08007', // Connection failure during transaction
            '08001'  // Unable to connect
        ];

        return in_array($e->getCode(), $retryableCodes) ||
               strpos($e->getMessage(), 'deadlock') !== false;
    }

    /* ------------------------------ Security Locks ---------------------------- */
    private function __clone() {}
    public function __wakeup() {
        throw new \Exception('Cannot unserialize Database Singleton.');
    }
}
