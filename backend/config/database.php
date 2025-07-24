<?php
/**
 * /backend/config/database.php
 *
 * Universal database abstraction for Carfify's PostgreSQL backend.
 * Optimized for Vercel deployment with Neon PostgreSQL.
 *
 * Usage:
 *   require_once __DIR__ . '/config/database.php';
 *   CarfifyDatabase::select($sql, [$bindings]);
 *
 * Environment variables (set in Vercel dashboard):
 *   DATABASE_URL (Neon connection string, preferred)
 *   Or individual: DB_HOST, DB_NAME, DB_USER, DB_PASS, DB_PORT
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
            try {
                $databaseUrl = getenv('DATABASE_URL');
                
                if ($databaseUrl) {
                    // Neon PostgreSQL on Vercel (DATABASE_URL format)
                    $dbOpts = parse_url($databaseUrl);
                    if ($dbOpts) {
                        $dsn = sprintf(
                            'pgsql:host=%s;port=%d;dbname=%s;user=%s;password=%s',
                            $dbOpts['host'] ?? 'localhost',
                            $dbOpts['port'] ?? 5432,
                            ltrim($dbOpts['path'] ?? '', '/'),
                            $dbOpts['user'] ?? '',
                            $dbOpts['pass'] ?? ''
                        );
                    } else {
                        throw new PDOException('Invalid DATABASE_URL format');
                    }
                } else {
                    // Fallback to individual ENV variables
                    $dsn = sprintf(
                        'pgsql:host=%s;port=%s;dbname=%s',
                        getenv('DB_HOST') ?: 'localhost',
                        getenv('DB_PORT') ?: 5432,
                        getenv('DB_NAME') ?: 'carfify'
                    );
                }

                $user = getenv('DATABASE_URL') ? '' : (getenv('DB_USER') ?: 'developer');
                $pass = getenv('DATABASE_URL') ? '' : (getenv('DB_PASS') ?: '');

                self::$pdo = new PDO(
                    $dsn,
                    $user,
                    $pass,
                    [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_TIMEOUT           => 5, // 5 second timeout for serverless
                        PDO::ATTR_PERSISTENT        => false, // Important for serverless
                    ]
                );
            } catch (PDOException $e) {
                // Log error securely
                error_log('DB connection error: ' . $e->getMessage());
                
                // Return proper JSON error for API endpoints
                http_response_code(503);
                echo json_encode([
                    'error' => 'Database currently unavailable',
                    'code'  => 'DB_CONNECTION_FAILED'
                ]);
                exit;
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
        try {
            $stmt = self::connection()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('DB select error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'error' => 'Database query failed',
                'code'  => 'DB_QUERY_ERROR'
            ]);
            exit;
        }
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
        try {
            $stmt = self::connection()->prepare($sql);
            $stmt->execute($params);
            return self::connection()->lastInsertId(implode('_', $pk));
        } catch (PDOException $e) {
            error_log('DB insert error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'error' => 'Database insert failed',
                'code'  => 'DB_INSERT_ERROR'
            ]);
            exit;
        }
    }

    /**
     * Executes a prepared non-SELECT statement (UPDATE, DELETE).
     */
    public static function exec(string $sql, array $params = []): int
    {
        try {
            $stmt = self::connection()->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log('DB exec error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'error' => 'Database operation failed',
                'code'  => 'DB_EXEC_ERROR'
            ]);
            exit;
        }
    }

    /**
     * Health check method for database connectivity
     */
    public static function ping(): bool
    {
        try {
            self::connection()->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
}
