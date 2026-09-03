import paramiko

HOST = '217.21.74.188'
PORT = 65002
USER = 'u390470426'
PASSWORD = 'Prmarketing@10786'

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(HOST, port=PORT, username=USER, password=PASSWORD, timeout=15)

stdin, stdout, stderr = ssh.exec_command("find domains/prmarketingventures.com/public_html -name 'posts.php'")
print("Found posts.php at:")
print(stdout.read().decode())
print("STDERR:", stderr.read().decode())
ssh.close()
