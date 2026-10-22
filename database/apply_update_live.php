<?php
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
