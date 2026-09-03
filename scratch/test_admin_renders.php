<?php
echo "=== COMPREHENSIVE ADMIN VIEWS VALIDATION ===" . PHP_EOL;

$views = [
    'login.php',
    'dashboard.php',
    'recruitments.php',
    'exams.php',
    'articles.php',
    'sources.php',
    'automation.php'
];

$allPassed = true;

foreach ($views as $view) {
    $script = "
        \$_SESSION['admin_user'] = ['username' => 'TestAdmin', 'email' => 'admin@govrecruit.ai', 'role' => 'ADMIN'];
        \$_SERVER['REQUEST_URI'] = '/admin/" . str_replace('.php', '', $view) . "';
        \$_SERVER['REQUEST_METHOD'] = 'GET';
        ob_start();
        require 'frontend/views/admin/{$view}';
        \$out = ob_get_clean();
        
        \$hasAdminCss = strpos(\$out, 'admin.css') !== false;
        \$noTicker = strpos(\$out, 'breaking-ticker') === false;
        \$noFooter = strpos(\$out, 'footer-grid') === false;
        
        if (\$hasAdminCss && \$noTicker && \$noFooter) {
            echo 'SUCCESS: ' . strlen(\$out) . ' bytes';
        } else {
            echo 'FAILED (Css:' . (\$hasAdminCss?'Y':'N') . ', NoTicker:' . (\$noTicker?'Y':'N') . ', NoFooter:' . (\$noFooter?'Y':'N') . ')';
        }
    ";

    $output = shell_exec("php -r " . escapeshellarg($script));
    $trimmed = trim($output ?? '');

    if (str_starts_with($trimmed, 'SUCCESS')) {
        echo "✓ {$view}: {$trimmed}" . PHP_EOL;
    } else {
        echo "✗ {$view}: {$trimmed}" . PHP_EOL;
        $allPassed = false;
    }
}

if ($allPassed) {
    echo PHP_EOL . "🎉 ALL 7 ADMIN PAGES RENDERED CLEANLY WITH NO PUBLIC CLUTTER!" . PHP_EOL;
} else {
    echo PHP_EOL . "❌ SOME VIEWS HAD ISSUES." . PHP_EOL;
}
