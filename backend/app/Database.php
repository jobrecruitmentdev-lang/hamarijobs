<?php
namespace App;

use PDO;
use PDOException;

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $host = getenv('MYSQL_HOST') ?: '127.0.0.1';
            $port = getenv('MYSQL_PORT') ?: '3306';
            $db   = getenv('MYSQL_DB') ?: 'job_recruitment_ai';
            $user = getenv('MYSQL_USER') ?: 'root';
            $pass = getenv('MYSQL_PASSWORD') ?: '';

            $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                // If local database connection fails, throw formatted error
                header('Content-Type: application/json');
                http_response_code(500);
                echo json_encode(['error' => 'Database connection failed', 'details' => $e->getMessage()]);
                exit;
            }
        }
        return self::$instance;
    }
}
