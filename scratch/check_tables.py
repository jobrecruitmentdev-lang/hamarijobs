import paramiko

HOST = '217.21.74.188'
PORT = 65002
USER = 'u390470426'
PASSWORD = 'Prmarketing@10786'

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(HOST, port=PORT, username=USER, password=PASSWORD, timeout=15)

script = """
require 'domains/prmarketingventures.com/public_html/backend/config/database.php';
$db = Database::getConnection();
$tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
echo json_encode($tables, JSON_PRETTY_PRINT);
"""

stdin, stdout, stderr = ssh.exec_command(f"php -r {repr(script)}")
print(stdout.read().decode())
print(stderr.read().decode())
ssh.close()
