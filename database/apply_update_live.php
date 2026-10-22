<?php
// Load .env explicitly for CLI execution
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (!empty($line) && !str_starts_with($line, '#') && str_contains($line, '=')) {
            list($key, $val) = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val, " \t\n\r\0\x0B\"'");
            $_ENV[$key] = $val;
            $_SERVER[$key] = $val;
            putenv("{$key}={$val}");
        }
    }
}

require_once __DIR__ . '/../backend/app/Database.php';
use App\Database;

try {
    echo "Connecting to database...\n";
    $db = Database::getConnection();
    
    $sqlFile = __DIR__ . '/update_63_articles.sql';
    if (!file_exists($sqlFile)) {
        die("SQL file not found: {$sqlFile}\n");
    }
    
    echo "Reading update_63_articles.sql...\n";
    $sql = file_get_contents($sqlFile);
    
    // Split queries by semicolon followed by newline
    $statements = preg_split('/;\s*(\r\n|\n)/', $sql);
    $statements = array_filter(array_map('trim', $statements));
    echo "Executing " . count($statements) . " SQL statements...\n";
    
    $executed = 0;
    foreach ($statements as $stmt) {
        if (!empty($stmt) && !str_starts_with($stmt, '--')) {
            $db->exec($stmt);
            $executed++;
        }
    }
    
    // Verify count in articles table
    $cntStmt = $db->query("SELECT count(*) as c, avg(quality_score) as avg_q FROM articles WHERE status = 'Published'");
    $res = $cntStmt->fetch();
    echo "SUCCESS: Executed {$executed} statements!\n";
    echo "Published Articles: {$res['c']} | Average Quality Score: {$res['avg_q']}\n";
    
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
