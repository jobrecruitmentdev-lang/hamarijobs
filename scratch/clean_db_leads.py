import os
import paramiko

# 1. Clean test records in Hostinger MySQL database
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
$db->exec('TRUNCATE TABLE pr_client_leads');
echo "Database table pr_client_leads successfully truncated and cleaned!\\n";
?>"""

sftp = ssh.open_sftp()
with sftp.file('domains/prmarketingventures.com/public_html/clean_leads.php', 'w') as f:
    f.write(script)
sftp.close()

stdin, stdout, stderr = ssh.exec_command("php domains/prmarketingventures.com/public_html/clean_leads.php && rm -f domains/prmarketingventures.com/public_html/clean_leads.php")
print(stdout.read().decode())
ssh.close()
