<?php
/**
 * doodle - Database connection (PDO)
 *
 * @package doodle
 * @author  Death Legion Team
 */

class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::connect();
        }
        return self::$instance;
    }

    private static function connect(): void
    {
        try {
            if (DB_TYPE === 'sqlite') {
                if (!file_exists(dirname(DB_PATH))) {
                    mkdir(dirname(DB_PATH), 0755, true);
                }
                $dsn = 'sqlite:' . DB_PATH;
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ];
                self::$instance = new PDO($dsn, null, null, $options);
                self::$instance->exec('PRAGMA foreign_keys = ON');
                self::$instance->exec('PRAGMA journal_mode = WAL');
            } else {
                $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ];
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            }
        } catch (PDOException $e) {
            if (ENVIRONMENT === 'development') {
                die('Database connection failed: ' . $e->getMessage());
            }
            die('Database connection failed. Please check your configuration.');
        }
    }

    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetch(string $sql, array $params = []): ?array
    {
        $stmt = self::query($sql, $params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function insert(string $table, array $data): int
    {
        $fields = array_keys($data);
        $placeholders = array_map(fn($f) => ':' . $f, $fields);
        $sql = 'INSERT INTO ' . $table . ' (' . implode(',', $fields) . ') VALUES (' . implode(',', $placeholders) . ')';
        self::query($sql, $data);
        return (int) self::getInstance()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = [];
        foreach (array_keys($data) as $field) {
            $set[] = $field . ' = :' . $field;
        }
        $sql = 'UPDATE ' . $table . ' SET ' . implode(', ', $set) . ' WHERE ' . $where;
        $stmt = self::query($sql, array_merge($data, $whereParams));
        return $stmt->rowCount();
    }

    public static function delete(string $table, string $where, array $params = []): int
    {
        $stmt = self::query('DELETE FROM ' . $table . ' WHERE ' . $where, $params);
        return $stmt->rowCount();
    }

    public static function count(string $table, string $where = '1=1', array $params = []): int
    {
        $row = self::fetch('SELECT COUNT(*) AS c FROM ' . $table . ' WHERE ' . $where, $params);
        return (int) ($row['c'] ?? 0);
    }
}
