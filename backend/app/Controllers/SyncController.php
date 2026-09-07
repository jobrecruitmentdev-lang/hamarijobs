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
        $expectedSecret = getenv('INTERNAL_API_SECRET') ?: ($_ENV['INTERNAL_API_SECRET'] ?? '');
        $providedSecret = $_SERVER['HTTP_X_INTERNAL_SECRET'] ?? '';

        if (empty($providedSecret) && isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $parts = explode(' ', $_SERVER['HTTP_AUTHORIZATION']);
            if (count($parts) === 2 && $parts[0] === 'Bearer') {
                $providedSecret = $parts[1];
            }
        }

        if (empty($expectedSecret) || empty($providedSecret) || !hash_equals($expectedSecret, $providedSecret)) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized: Invalid internal secret']);
            exit;
        }
    }

    public function syncJobs(): void {
        $body = file_get_contents('php://input');
        $payload = json_decode($body, true);
        $jobs = $payload['jobs'] ?? [];
        $recruitments = $payload['recruitments'] ?? [];

        if (empty($jobs) && empty($recruitments)) {
            http_response_code(400);
            echo json_encode(['error' => 'No jobs or recruitments provided in payload']);
            return;
        }

        $jobsSynced = 0;
        $recSynced = 0;

        // 1. Sync Recruitments if provided
        if (!empty($recruitments)) {
            foreach ($recruitments as $rec) {
                $title = trim($rec['title'] ?? 'Official Government Recruitment');
                $org = trim($rec['organization_name'] ?? $rec['org'] ?? 'Gov of India');
                $slug = trim($rec['slug'] ?? strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', "{$org}-{$title}-2026")));
                $vacancies = (int)($rec['total_vacancies'] ?? $rec['vac'] ?? 0);
                $qual = $rec['qualification_level'] ?? 'Graduate / 12th Pass as per notification';
                $applyUrl = $rec['official_apply_url'] ?? $rec['apply_url'] ?? 'https://gov.in';
                $noticeUrl = $rec['primary_notification_url'] ?? $rec['pdf_url'] ?? $applyUrl;
                $websiteUrl = $rec['official_website_url'] ?? 'https://gov.in';
                $stateCode = $rec['state_code'] ?? 'ALL';
                $summary = $rec['summary'] ?? "Official Recruitment for {$title} by {$org}. Total vacancies: {$vacancies}.";
                $advtNo = $rec['advertisement_number'] ?? $rec['advt_no'] ?? '2026/01';
                $status = $rec['status'] ?? 'Active';
                $reviewStatus = $rec['review_status'] ?? 'VERIFIED';
                $recUuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));

                // Check if already exists by slug
                $check = $this->db->prepare("SELECT id FROM recruitments WHERE slug = ? LIMIT 1");
                $check->execute([$slug]);
                $existing = $check->fetch(PDO::FETCH_ASSOC);

                if ($existing) {
                    $stmt = $this->db->prepare("
                        UPDATE recruitments SET
                            title = ?, organization_name = ?, total_vacancies = ?, qualification_level = ?,
                            official_apply_url = ?, primary_notification_url = ?, status = ?, review_status = ?,
                            summary = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$title, $org, $vacancies, $qual, $applyUrl, $noticeUrl, $status, $reviewStatus, $summary, $existing['id']]);
                    $recId = $existing['id'];
                } else {
                    $stmt = $this->db->prepare("
                        INSERT INTO recruitments (
                            recruitment_uuid, title, slug, organization_name, advertisement_number,
                            notification_number, year, total_vacancies, status, review_status,
                            primary_notification_url, official_website_url, official_apply_url,
                            state_code, qualification_level, summary, is_verified, verified_at,
                            created_at, updated_at
                        ) VALUES (
                            ?, ?, ?, ?, ?,
                            ?, 2026, ?, ?, ?,
                            ?, ?, ?,
                            ?, ?, ?, 1, NOW(),
                            NOW(), NOW()
                        )
                    ");
                    $stmt->execute([
                        $recUuid, $title, $slug, $org, $advtNo,
                        $advtNo, $vacancies, $status, $reviewStatus,
                        $noticeUrl, $websiteUrl, $applyUrl,
                        $stateCode, $qual, $summary
                    ]);
                    $recId = $this->db->lastInsertId();
                }

                // Sync timeline events if provided
                if (!empty($rec['events']) && is_array($rec['events']) && $recId) {
                    foreach ($rec['events'] as $ev) {
                        $evName = $ev['name'] ?? 'Registration Window';
                        $evDate = $ev['date'] ?? date('Y-m-d');
                        $evType = $ev['type'] ?? 'REGISTRATION_START';
                        
                        $chkEv = $this->db->prepare("SELECT id FROM recruitment_events WHERE recruitment_id = ? AND event_type = ? LIMIT 1");
                        $chkEv->execute([$recId, $evType]);
                        if (!$chkEv->fetch()) {
                            $insEv = $this->db->prepare("
                                INSERT INTO recruitment_events (recruitment_id, event_name, event_type, event_date, is_tentative, created_at)
                                VALUES (?, ?, ?, ?, 0, NOW())
                            ");
                            $insEv->execute([$recId, $evName, $evType, $evDate]);
                        }
                    }
                }

                $recSynced++;
            }
        }

        // 2. Sync Jobs for candidate portal
        if (!empty($jobs)) {
            foreach ($jobs as $job) {
                $title = trim($job['title'] ?? 'Government Job');
                $org = trim($job['org'] ?? $job['department'] ?? 'Gov of India');
                $vac = $job['vac'] ?? $job['total_vacancies'] ?? null;
                $sal = $job['sal'] ?? $job['salary_range'] ?? '₹35,400+ as per 7th CPC';
                if (is_numeric($sal)) {
                    $sal = "₹{$sal}+ as per 7th CPC";
                }
                $desc = $job['desc'] ?? $job['description'] ?? "Official Recruitment for {$title} by {$org}.";
                $jobTitle = (str_starts_with($title, $org)) ? $title : "{$org} {$title}";

                // Check if already exists by title
                $chkJob = $this->db->prepare("SELECT id FROM jobs WHERE title = ? LIMIT 1");
                $chkJob->execute([$jobTitle]);
                $existingJob = $chkJob->fetch(PDO::FETCH_ASSOC);

                if ($existingJob) {
                    $upd = $this->db->prepare("
                        UPDATE jobs SET
                            description = ?, salary_range = ?, department = ?, status = 'OPEN', updated_at = NOW()
                        WHERE id = ?
                    ");
                    $upd->execute([$desc, $sal, $org, $existingJob['id']]);
                } else {
                    $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
                    $ins = $this->db->prepare("
                        INSERT INTO jobs (id, title, description, job_type, salary_range, work_mode, status, department, category, is_govt, created_at, updated_at)
                        VALUES (?, ?, ?, 'Full-time', ?, 'On-site', 'OPEN', ?, 'Government', 1, NOW(), NOW())
                    ");
                    $ins->execute([$uuid, $jobTitle, $desc, $sal, $org]);
                }
                $jobsSynced++;
            }
        }

        echo json_encode([
            'success' => true,
            'message' => "Successfully synced {$recSynced} recruitments and {$jobsSynced} candidate jobs into live platform.",
            'recruitments_synced' => $recSynced,
            'jobs_synced' => $jobsSynced
        ]);
    }
}
