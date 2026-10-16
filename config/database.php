<?php
/**
 * Database Connection – Singleton PDO Wrapper
 * Provides a single, reusable PDO instance throughout the application.
 */

require_once __DIR__ . '/config.php';

class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    /**
     * Private constructor: builds the PDO connection.
     * @throws PDOException on connection failure.
     */
    private function __construct()
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,   // Use real prepared statements
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ];

        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Never expose raw DB errors to the client
            error_log('DB Connection failed: ' . $e->getMessage());
            throw new RuntimeException('Database service unavailable. Please try again later.');
        }
    }

    /**
     * Returns the singleton Database instance.
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    /**
     * Exposes the underlying PDO object.
     */
    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    /**
     * Shorthand: prepare & execute a statement with bound parameters.
     *
     * @param  string $sql    Parameterised SQL query
     * @param  array  $params Associative or positional params
     * @return PDOStatement
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch a single row.
     */
    public function fetchOne(string $sql, array $params = []): array|false
    {
        return $this->query($sql, $params)->fetch();
    }

    /**
     * Fetch all matching rows.
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Returns the last inserted row ID.
     */
    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    // Prevent cloning and unserialization of the singleton
    private function __clone() {}
    public function __wakeup(): never
    {
        throw new \Exception('Cannot unserialize a singleton.');
    }
}
