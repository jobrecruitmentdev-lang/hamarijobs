import paramiko
import base64

HOST = '217.21.74.188'
PORT = 65002
USER = 'u390470426'
PASSWORD = 'Prmarketing@10786'

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(HOST, port=PORT, username=USER, password=PASSWORD, timeout=15)

script = """<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once '/home/u390470426/domains/prmarketingventures.com/public_html/backend/config/database.php';
$db = Database::getConnection();
$tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
print_r($tables);
"""
b64 = base64.b64encode(script.encode()).decode()
cmd = f"php -r 'eval(base64_decode(\"{b64}\"));'"
stdin, stdout, stderr = ssh.exec_command(cmd)
print("Tables in MySQL:")
print(stdout.read().decode())
print("STDERR:", stderr.read().decode())
ssh.close()
