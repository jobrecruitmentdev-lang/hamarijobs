import os

lead_repo_file = r"C:\hk\prmarketing\backend\repositories\LeadRepository.php"

content = """<?php
/**
 * Client Lead Repository (PR Marketing Ventures Enterprise)
 */

require_once __DIR__ . '/../config/database.php';

class LeadRepository {
    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? Database::getConnection();
        $this->ensureTable();
    }

    private function ensureTable(): void {
        $sql = "CREATE TABLE IF NOT EXISTS pr_client_leads (
            id VARCHAR(36) PRIMARY KEY,
            full_name VARCHAR(255) NOT NULL,
            phone_number VARCHAR(50) NOT NULL,
            whatsapp_number VARCHAR(50) NULL,
            business_name VARCHAR(255) NULL,
            website_url VARCHAR(255) NULL,
            business_stage VARCHAR(100) NULL,
            tool_used VARCHAR(100) NULL,
            status VARCHAR(50) DEFAULT 'New Lead',
            notes TEXT NULL,
            ip_address VARCHAR(45) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $this->db->exec($sql);
    }

    public function create(array $data): string {
        $id = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        $fullName = trim($data['fullName'] ?? $data['full_name'] ?? '');
        $phone = trim($data['phoneNumber'] ?? $data['phone_number'] ?? '');
        $whatsapp = trim($data['whatsappNumber'] ?? $data['whatsapp_number'] ?? $phone);
        $businessName = trim($data['websiteName'] ?? $data['business_name'] ?? '');
        $websiteUrl = trim($data['websiteUrl'] ?? $data['website_url'] ?? '');
        $businessStage = trim($data['businessStage'] ?? $data['business_stage'] ?? 'General Inquiry');
        $toolUsed = trim($data['toolUsed'] ?? $data['tool_used'] ?? 'Marketing Tool');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        $stmt = $this->db->prepare("INSERT INTO pr_client_leads (
            id, full_name, phone_number, whatsapp_number, business_name, website_url, business_stage, tool_used, status, ip_address, created_at
        ) VALUES (
            :id, :full_name, :phone_number, :whatsapp_number, :business_name, :website_url, :business_stage, :tool_used, 'New Lead', :ip, NOW()
        )");

        $stmt->execute([
            ':id'              => $id,
            ':full_name'       => $fullName,
            ':phone_number'    => $phone,
            ':whatsapp_number' => $whatsapp,
            ':business_name'   => $businessName,
            ':website_url'     => $websiteUrl,
            ':business_stage'  => $businessStage,
            ':tool_used'       => $toolUsed,
            ':ip'              => $ip
        ]);

        return $id;
    }

    public function getAll(int $limit = 100, int $offset = 0, ?string $search = null, ?string $status = null, ?string $tool = null): array {
        $sql = "SELECT * FROM pr_client_leads WHERE 1=1";
        $params = [];

        if ($status && $status !== 'All') {
            $sql .= " AND status = :status";
            $params[':status'] = $status;
        }

        if ($tool && $tool !== 'All') {
            $sql .= " AND tool_used = :tool";
            $params[':tool'] = $tool;
        }

        if ($search) {
            $sql .= " AND (full_name LIKE :search OR phone_number LIKE :search OR business_name LIKE :search OR website_url LIKE :search OR business_stage LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);

        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStats(): array {
        $total = $this->db->query("SELECT COUNT(*) FROM pr_client_leads")->fetchColumn() ?: 0;
        $newLeads = $this->db->query("SELECT COUNT(*) FROM pr_client_leads WHERE status = 'New Lead'")->fetchColumn() ?: 0;
        $today = $this->db->query("SELECT COUNT(*) FROM pr_client_leads WHERE DATE(created_at) = CURDATE()")->fetchColumn() ?: 0;
        $tools = $this->db->query("SELECT DISTINCT tool_used FROM pr_client_leads WHERE tool_used IS NOT NULL AND tool_used != ''")->fetchAll(PDO::FETCH_COLUMN);

        return [
            'total' => (int)$total,
            'new_leads' => (int)$newLeads,
            'today' => (int)$today,
            'tools' => $tools
        ];
    }

    public function updateStatus(string $id, string $status, ?string $notes = null): bool {
        $stmt = $this->db->prepare("UPDATE pr_client_leads SET status = :status, notes = COALESCE(:notes, notes), updated_at = NOW() WHERE id = :id");
        return $stmt->execute([':status' => $status, ':notes' => $notes, ':id' => $id]);
    }

    public function delete(string $id): bool {
        $stmt = $this->db->prepare("DELETE FROM pr_client_leads WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
"""

with open(lead_repo_file, "w", encoding="utf-8") as f:
    f.write(content.strip())

print(f"Created {lead_repo_file} successfully!")
