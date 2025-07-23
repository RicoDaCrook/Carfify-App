<?php
/**
 * /backend/config/database.php
 *
 * Universal database abstraction for Carfify's PostgreSQL backend.
 *
 * Usage:
 *   require_once __DIR__ . '/config/database.php';
 *   CarfifyDatabase::select($sql, [$bindings]);
 *
 * Environment variables required:
 *   DB_HOST, DB_NAME, DB_USER, DB_PASS, DB_PORT
 *
 * @package Carfify
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Carfify;

use PDO;
use PDOException;
use PDOStatement;

final class Database
{
    /** @var PDO|null */
    private static ?PDO $pdo = null;

    /**
     * Returns the singleton PDO connection.
     */
    private static function connection(): PDO
    {
        if (self::$pdo === null) {
            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                getenv('DB_HOST') ?: 'localhost',
                getenv('DB_PORT') ?: 5432,
                getenv('DB_NAME') ?: 'carfify',
            );
            try {
                self::$pdo = new PDO(
                    $dsn,
                    getenv('DB_USER') ?: 'developer',
                    getenv('DB_PASS') ?: '',
                    [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]
                );
            } catch (PDOException $e) {
                // Do NOT expose credentials, log server-side
                error_log('DB connection error: ' . $e->getMessage());
                http_response_code(500);
                exit(json_encode(['error' => 'Database unavailable']));
            }
        }

        return self::$pdo;
    }

    /**
     * Executes a prepared SELECT and returns all rows.
     *
     * @param string $sql    The SQL statement (placeholders with ?)
     * @param array  $params Bindings for each placeholder
     * @return array<int,array<string,mixed>>
     */
    public static function select(string $sql, array $params = []): array
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Executes a prepared INSERT and returns the lastInsertId.
     *
     * @param string   $sql    The SQL statement (placeholders with ?)
     * @param array    $params Bindings for each placeholder
     * @param string[] $pk     Primary key field(s) (only needed for multi-field sequences)
     * @return int|string Last insert ID
     */
    public static function insert(string $sql, array $params = [], array $pk = ['id'])
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        return self::connection()->lastInsertId(implode('_', $pk));
    }

    /**
     * Executes a prepared non-SELECT statement (UPDATE, DELETE).
     */
    public static function exec(string $sql, array $params = []): int
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }
}
