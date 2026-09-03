import paramiko

HOST = '217.21.74.188'
PORT = 65002
USER = 'u390470426'
PASSWORD = 'Prmarketing@10786'

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(HOST, port=PORT, username=USER, password=PASSWORD, timeout=15)

sftp = ssh.open_sftp()
with sftp.file('domains/prmarketingventures.com/public_html/check_leads_db.php', 'w') as f:
    f.write("""<?php
require_once __DIR__ . '/backend/config/database.php';
$db = Database::getConnection();
$leads = $db->query('SELECT * FROM pr_client_leads ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
echo "TOTAL LEADS IN DB: " . count($leads) . "\\n";
foreach ($leads as $l) {
    echo "- [{$l['created_at']}] {$l['full_name']} | Phone: {$l['phone_number']} | Brand: {$l['business_name']} | Stage: {$l['business_stage']} | Tool: {$l['tool_used']} | Status: {$l['status']}\\n";
}
""")
sftp.close()

stdin, stdout, stderr = ssh.exec_command("php domains/prmarketingventures.com/public_html/check_leads_db.php && rm -f domains/prmarketingventures.com/public_html/check_leads_db.php")
print(stdout.read().decode())
print(stderr.read().decode())
ssh.close()
