<?php
namespace App\Controllers;

use App\Database;
use PDO;

class SyncController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->validateInternalSecret();
    }

    private function validateInternalSecret(): void {
        $expectedSecret = getenv('INTERNAL_API_SECRET') ?: 'gov_sec_sync_k9a2b8e4f1c7d3a5e8b0c2d4e6f8a0b2';
        $providedSecret = $_SERVER['HTTP_X_INTERNAL_SECRET'] ?? '';

        if (empty($providedSecret) && isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $parts = explode(' ', $_SERVER['HTTP_AUTHORIZATION']);
            if (count($parts) === 2 && $parts[0] === 'Bearer') {
                $providedSecret = $parts[1];
            }
        }

        if ($providedSecret !== $expectedSecret) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized: Invalid internal secret']);
            exit;
        }
    }

    public function syncJobs(): void {
        $body = file_get_contents('php://input');
        $payload = json_decode($body, true);
        $jobs = $payload['jobs'] ?? [];

        if (empty($jobs)) {
            http_response_code(400);
            echo json_encode(['error' => 'No jobs provided in payload']);
            return;
        }

        $inserted = 0;
        foreach ($jobs as $job) {
            $title = $job['title'] ?? 'Government Job';
            $org = $job['org'] ?? 'Gov of India';
            $vac = $job['vac'] ?? null;
            $sal = $job['sal'] ?? 35400;
            $url = $job['url'] ?? 'https://gov.in';
            $desc = $job['desc'] ?? '';
            $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));

            $stmt = $this->db->prepare("
                INSERT INTO jobs (id, title, description, job_type, salary_range, work_mode, status, department, category, is_govt, created_at)
                VALUES (?, ?, ?, 'Full-time', ?, 'On-site', 'OPEN', ?, 'Government', 1, NOW())
            ");
            $stmt->execute([$uuid, "{$org} {$title}", $desc, "₹{$sal}+ as per 7th CPC", $org]);
            $inserted++;
        }

        echo json_encode([
            'success' => true,
            'message' => "Successfully synced {$inserted} government jobs into platform."
        ]);
    }
}
