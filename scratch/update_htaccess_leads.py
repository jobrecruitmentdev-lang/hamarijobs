import os

htaccess_path = r"C:\hk\prmarketing\website\public\.htaccess"

with open(htaccess_path, "r", encoding="utf-8") as f:
    content = f.read()

target = "  RewriteRule ^admin/media(\\.php)?$ backend/admin/media.php [QSA,L]"
replacement = """  RewriteRule ^admin/media(\\.php)?$ backend/admin/media.php [QSA,L]
  RewriteRule ^admin/leads(\\.php)?$ backend/admin/leads.php [QSA,L]
  RewriteRule ^api/leads(\\.php)?$ backend/api/leads.php [QSA,L]"""

if "^admin/leads" not in content:
    content = content.replace(target, replacement)
    with open(htaccess_path, "w", encoding="utf-8") as f:
        f.write(content)
    print("Updated .htaccess with /admin/leads and /api/leads rewrite rules!")
else:
    print(".htaccess already has leads rewrite rules.")
