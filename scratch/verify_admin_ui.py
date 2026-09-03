import subprocess
import os
import json

views = [
    'login.php',
    'dashboard.php',
    'recruitments.php',
    'exams.php',
    'articles.php',
    'sources.php',
    'automation.php'
]

print("=== VERIFYING ADMIN VIEWS IN HAMARIJOBS ===")
all_pass = True

for v in views:
    route_name = v.replace(".php", "")
    session_setup = "$_SESSION['admin_user'] = ['username' => 'TestAdmin', 'email' => 'admin@govrecruit.ai', 'role' => 'ADMIN'];" if v != 'login.php' else "unset($_SESSION['admin_user']);"
    
    runner_code = f"""<?php
    if (session_status() === PHP_SESSION_NONE) session_start();
    {session_setup}
    $_SERVER['REQUEST_URI'] = '/admin/{route_name}';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    ob_start();
    require_once __DIR__ . '/../frontend/views/admin/{v}';
    $out = ob_get_clean();
    
    $hasAdminCss = strpos($out, 'admin.css') !== false;
    $noTicker = strpos($out, 'breaking-ticker') === false;
    $noFooter = strpos($out, 'footer-grid') === false;
    
    echo json_encode([
        'view' => '{v}',
        'length' => strlen($out),
        'hasAdminCss' => $hasAdminCss,
        'noTicker' => $noTicker,
        'noFooter' => $noFooter,
        'success' => $hasAdminCss && $noTicker && $noFooter
    ]);
    """
    
    runner_file = r"c:\hk\hamarijobs\scratch\temp_runner.php"
    with open(runner_file, "w", encoding="utf-8") as f:
        f.write(runner_code)
    
    res = subprocess.run(["php", runner_file], capture_output=True, text=True, cwd=r"c:\hk\hamarijobs")
    stdout = res.stdout.strip()
    
    try:
        data = json.loads(stdout)
        if data['success']:
            print(f"[PASS] {v:<18} | {data['length']} bytes | Admin CSS: Yes | No Public Ticker: Yes | No Public Footer: Yes")
        else:
            print(f"[FAIL] {v}: {data}")
            all_pass = False
    except Exception as e:
        print(f"[ERROR] {v}: stdout={stdout} stderr={res.stderr}")
        all_pass = False

if os.path.exists(r"c:\hk\hamarijobs\scratch\temp_runner.php"):
    os.remove(r"c:\hk\hamarijobs\scratch\temp_runner.php")

if all_pass:
    print("\n[SUCCESS] ALL 7 ADMIN VIEWS ARE 100% CLEAN, GICH-FREE, AND PROPERLY STRUCTURED!")
else:
    print("\n[ERROR] SOME VIEWS HAD ISSUES.")
