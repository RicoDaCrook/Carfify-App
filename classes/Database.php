<?php
/**
 * Singleton-Klasse für die PDO-Verbindung zur PostgreSQL-Datenbank.
 * Stellt sicher, dass nur eine Instanz der Datenbankverbindung existiert.
 */
class Database
{
    /** @var PDO|null Die einzige Instanz der PDO-Verbindung */
    private static ?PDO $instance = null;

    /** @var array Konfiguration aus Umgebungsvariablen */
    private static array $config = [
        'host'     => 'localhost',
        'port'     => 5432,
        'dbname'   => 'carfify',
        'user'     => 'carfify_user',
        'password' => 'carfify_pass',
    ];

    /**
     * Privater Konstruktor verhindert direkte Instanziierung.
     */
    private function __construct() {}

    /**
     * Gibt die PDO-Datenbankverbindung zurück.
     * Erstellt die Verbindung bei Bedarf neu.
     *
     * @return PDO
     */
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'pgsql:host=%s;port=%d;dbname=%s',
                self::$config['host'],
                self::$config['port'],
                self::$config['dbname']
            );
            self::$instance = new PDO(
                $dsn,
                self::$config['user'],
                self::$config['password'],
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        }
        return self::$instance;
    }

    /**
     * Verhindert das Klonen der Instanz (Singleton-Pattern).
     */
    private function __clone() {}

    /**
     * Verhindert das Deserialisieren der Instanz (Singleton-Pattern).
     */
    public function __wakeup() {}
}
