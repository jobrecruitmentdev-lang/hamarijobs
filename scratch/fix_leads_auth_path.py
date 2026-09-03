import os

leads_file = r"C:\hk\prmarketing\backend\admin\leads.php"

with open(leads_file, "r", encoding="utf-8") as f:
    code = f.read()

code = code.replace(
    "require_once __DIR__ . '/../auth_middleware.php';",
    "require_once __DIR__ . '/auth_middleware.php';"
)

with open(leads_file, "w", encoding="utf-8") as f:
    f.write(code)

print("Fixed auth_middleware path in leads.php!")
