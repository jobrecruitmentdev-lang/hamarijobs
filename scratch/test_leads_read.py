import paramiko

HOST = '217.21.74.188'
PORT = 65002
USER = 'u390470426'
PASSWORD = 'Prmarketing@10786'

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(HOST, port=PORT, username=USER, password=PASSWORD, timeout=15)

script = """<?php
require_once '/home/u390470426/domains/prmarketingventures.com/public_html/backend/config/database.php';
$db = Database::getConnection();
$stmt = $db->query('SELECT full_name, phone_number, business_name, website_url, business_stage, tool_used, status, created_at FROM pr_client_leads ORDER BY created_at DESC');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "TOTAL ROWS: " . count($rows) . "\\n";
foreach ($rows as $r) {
    echo "• " . $r['full_name'] . " | " . $r['phone_number'] . " | " . $r['business_name'] . " | " . $r['tool_used'] . " | " . $r['status'] . "\\n";
}
?>"""

sftp = ssh.open_sftp()
with sftp.file('domains/prmarketingventures.com/public_html/test_leads_read.php', 'w') as f:
    f.write(script)
sftp.close()

stdin, stdout, stderr = ssh.exec_command("php domains/prmarketingventures.com/public_html/test_leads_read.php && rm -f domains/prmarketingventures.com/public_html/test_leads_read.php")
print(stdout.read().decode())
print("STDERR:", stderr.read().decode())
ssh.close()
