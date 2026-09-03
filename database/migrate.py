import pymysql
import os
import glob
import re

def run_migration():
    conn = pymysql.connect(
        host="127.0.0.1",
        user="root",
        password="",
        database="job_recruitment_ai",
        autocommit=True
    )
    cur = conn.cursor()
    cur.execute("SET FOREIGN_KEY_CHECKS = 0;")

    schema_dir = os.path.dirname(os.path.abspath(__file__))
    schema_files = sorted(glob.glob(os.path.join(schema_dir, "schema", "*.sql")))
    
    print("Executing migrations from:", schema_dir)
    
    for sf in schema_files:
        filename = os.path.basename(sf)
        if "06_procedures_triggers.sql" in filename:
            # Stored procs / triggers can have specific MariaDB syntax, we can handle or skip
            continue
            
        print(f"Applying {filename}...")
        with open(sf, "r", encoding="utf-8") as f:
            content = f.read()

        # Remove single line comments
        lines = []
        for line in content.splitlines():
            stripped = line.strip()
            if not stripped.startswith("--") and not stripped.startswith("/*"):
                lines.append(line)
        cleaned_sql = "\n".join(lines)

        statements = cleaned_sql.split(";")
        for stmt in statements:
            stmt = stmt.strip()
            if stmt:
                try:
                    cur.execute(stmt)
                except Exception as e:
                    err_msg = str(e).lower()
                    if "already exists" not in err_msg and "duplicate" not in err_msg:
                        print(f"  [Warning in {filename}]: {e}")

    cur.execute("SET FOREIGN_KEY_CHECKS = 1;")
    cur.execute("SHOW TABLES;")
    tables = [row[0] for row in cur.fetchall()]
    print(f"\nMigration Complete! Total tables in job_recruitment_ai: {len(tables)}")
    print("Tables list:", sorted(tables))
    conn.close()

if __name__ == "__main__":
    run_migration()
