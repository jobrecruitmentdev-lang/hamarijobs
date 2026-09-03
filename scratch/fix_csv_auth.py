import os

admin_leads_file = r"C:\hk\prmarketing\backend\admin\leads.php"

with open(admin_leads_file, "r", encoding="utf-8") as f:
    code = f.read()

old_block = """// 1. Handle CSV Export
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    require_once __DIR__ . '/layout/header.php'; // Ensures authentication"""

new_block = """// 1. Handle CSV Export
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    require_once __DIR__ . '/../auth_middleware.php';
    requireAdminAuth();"""

code = code.replace(old_block, new_block)

with open(admin_leads_file, "w", encoding="utf-8") as f:
    f.write(code)

print("Updated leads.php CSV export auth check cleanly!")
