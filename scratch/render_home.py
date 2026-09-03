import subprocess

php_code = """<?php
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';
ob_start();
require_once __DIR__ . '/../frontend/views/home.php';
$html = ob_get_clean();
file_put_contents(__DIR__ . '/rendered_home.html', $html);
echo "HTML rendered successfully, length: " . strlen($html) . " bytes";
"""

with open(r"c:\hk\hamarijobs\scratch\render_home.php", "w", encoding="utf-8") as f:
    f.write(php_code)

res = subprocess.run(["php", r"c:\hk\hamarijobs\scratch\render_home.php"], capture_output=True, text=True, cwd=r"c:\hk\hamarijobs")
print(res.stdout)
if res.stderr:
    print("STDERR:", res.stderr)
