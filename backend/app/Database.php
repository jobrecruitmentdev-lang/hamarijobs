<?php
namespace App;

use PDO;
use PDOException;

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            // Support $_ENV, $_SERVER, and getenv() across all server environments (Local, Shared, VPS, Docker)
            $host = $_ENV['MYSQL_HOST'] ?? $_SERVER['MYSQL_HOST'] ?? getenv('MYSQL_HOST') ?: '127.0.0.1';
            $port = $_ENV['MYSQL_PORT'] ?? $_SERVER['MYSQL_PORT'] ?? getenv('MYSQL_PORT') ?: '3306';
            $db   = $_ENV['MYSQL_DB'] ?? $_SERVER['MYSQL_DB'] ?? getenv('MYSQL_DB') ?: 'job_recruitment_ai';
            $user = $_ENV['MYSQL_USER'] ?? $_SERVER['MYSQL_USER'] ?? getenv('MYSQL_USER') ?: 'root';
            $pass = $_ENV['MYSQL_PASSWORD'] ?? $_SERVER['MYSQL_PASSWORD'] ?? getenv('MYSQL_PASSWORD') ?: '';

            $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                error_log("Database Connection Error: " . $e->getMessage());

                $isDebug = (
                    ($_ENV['APP_DEBUG'] ?? '') === 'true' ||
                    ($_SERVER['APP_DEBUG'] ?? '') === 'true' ||
                    getenv('APP_DEBUG') === 'true' ||
                    getenv('APP_ENV') === 'development'
                );

                $uri = $_SERVER['REQUEST_URI'] ?? '';
                $isApi = str_contains($uri, '/api/') || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

                http_response_code(500);

                if ($isApi) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'error' => 'Database connection failed',
                        'details' => $isDebug ? $e->getMessage() : 'An internal database error occurred. Please verify your database configuration.'
                    ]);
                } else {
                    header('Content-Type: text/html; charset=UTF-8');
                    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Database Service Unavailable — HamariJobs</title><style>body{font-family:system-ui,-apple-system,sans-serif;background:#0d1117;color:#c9d1d9;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;}div{background:#161b22;border:1px solid #30363d;border-radius:8px;padding:2rem;max-width:560px;text-align:center;box-shadow:0 8px 24px rgba(0,0,0,0.5);}h1{color:#f85149;margin-top:0;font-size:1.5rem;}p{font-size:0.95rem;line-height:1.5;color:#8b949e;}code{background:#21262d;padding:0.2rem 0.4rem;border-radius:4px;color:#e6edf3;font-size:0.85rem;display:inline-block;margin-top:0.5rem;}</style></head><body><div><h1>⚠️ Database Configuration Required</h1><p>HamariJobs was unable to connect to the configured MySQL database server.</p><p>Please ensure your <code>.env</code> file or database credentials are correctly configured in Hostinger hPanel.</p>' . ($isDebug ? '<p><code>' . htmlspecialchars($e->getMessage()) . '</code></p>' : '') . '</div></body></html>';
                }
                exit;
            }
        }
        return self::$instance;
    }
}
