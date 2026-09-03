import os

header_file = r"C:\hk\prmarketing\backend\admin\layout\header.php"
sidebar_file = r"C:\hk\prmarketing\backend\admin\layout\sidebar.php"

# 1. Fix header.php
with open(header_file, "r", encoding="utf-8") as f:
    header_content = f.read()

if "require_once __DIR__ . '/../../config/database.php';" not in header_content:
    header_content = header_content.replace(
        "if (session_status() === PHP_SESSION_NONE) {\n    session_start();\n}",
        "if (session_status() === PHP_SESSION_NONE) {\n    session_start();\n}\nrequire_once __DIR__ . '/../../config/database.php';"
    )
    with open(header_file, "w", encoding="utf-8") as f:
        f.write(header_content)
    print(f"Updated {header_file} with database.php include!")

# 2. Fix sidebar.php
with open(sidebar_file, "r", encoding="utf-8") as f:
    sidebar_content = f.read()

if "require_once __DIR__ . '/../../config/database.php';" not in sidebar_content:
    sidebar_content = sidebar_content.replace(
        "<?php\n/**\n * PR Marketing Ventures — Left Navigation Sidebar (Warm Cream & Light Brown)\n */",
        "<?php\n/**\n * PR Marketing Ventures — Left Navigation Sidebar (Warm Cream & Light Brown)\n */\nrequire_once __DIR__ . '/../../config/database.php';"
    )
    with open(sidebar_file, "w", encoding="utf-8") as f:
        f.write(sidebar_content)
    print(f"Updated {sidebar_file} with database.php include!")

print("Database include fix completed!")
