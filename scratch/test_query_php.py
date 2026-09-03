import paramiko

HOST = '217.21.74.188'
PORT = 65002
USER = 'u390470426'
PASSWORD = 'Prmarketing@10786'

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(HOST, port=PORT, username=USER, password=PASSWORD, timeout=15)

sftp = ssh.open_sftp()
with sftp.file('domains/prmarketingventures.com/public_html/test_db_query.php', 'w') as f:
    f.write("""<?php
require_once __DIR__ . '/backend/config/database.php';
$db = Database::getConnection();
$posts = $db->query('SELECT id, title, slug, status FROM pr_posts WHERE deleted_at IS NULL')->fetchAll(PDO::FETCH_ASSOC);
echo "POSTS COUNT: " . count($posts) . "\\n";
foreach ($posts as $p) {
    $secCount = $db->query("SELECT COUNT(*) as c FROM pr_post_sections WHERE post_id = '{$p['id']}'")->fetch(PDO::FETCH_ASSOC)['c'];
    $faqCount = $db->query("SELECT COUNT(*) as c FROM pr_post_faqs WHERE post_id = '{$p['id']}'")->fetch(PDO::FETCH_ASSOC)['c'];
    echo "- ID: {$p['id']} | Slug: {$p['slug']} | Status: {$p['status']} | Secs: {$secCount} | FAQs: {$faqCount}\\n";
    echo "  Title: {$p['title']}\\n";
}
""")
sftp.close()

stdin, stdout, stderr = ssh.exec_command("php domains/prmarketingventures.com/public_html/test_db_query.php && rm -f domains/prmarketingventures.com/public_html/test_db_query.php")
print(stdout.read().decode())
print(stderr.read().decode())
ssh.close()
