import os
import shutil

src_htaccess = r"C:\hk\prmarketing\website\public\.htaccess"
out_htaccess = r"C:\hk\prmarketing\website\out\.htaccess"

shutil.copyfile(src_htaccess, out_htaccess)
print(f"Copied {src_htaccess} -> {out_htaccess}")

# Also update deploy_to_hostinger.py to ensure this happens every time
deploy_script = r"C:\hk\prmarketing\deploy_to_hostinger.py"
with open(deploy_script, "r", encoding="utf-8") as f:
    code = f.read()

if "shutil.copyfile(HTACCESS_LOCAL, os.path.join(LOCAL_DIR, '.htaccess'))" not in code:
    code = code.replace(
        "    update_htaccess_css(HTACCESS_LOCAL, css_filename)\n",
        "    update_htaccess_css(HTACCESS_LOCAL, css_filename)\n    import shutil\n    shutil.copyfile(HTACCESS_LOCAL, os.path.join(LOCAL_DIR, '.htaccess'))\n"
    )
    with open(deploy_script, "w", encoding="utf-8") as f:
        f.write(code)
    print("Updated deploy_to_hostinger.py to auto-sync .htaccess before packing!")
