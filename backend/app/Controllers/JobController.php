<?php
namespace App\Controllers;

use App\Database;
use PDO;

class JobController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function listJobs(): void {
        $search = trim($_GET['q'] ?? '');
        $state = trim($_GET['state'] ?? '');
        $qualification = trim($_GET['qualification'] ?? '');
        $category = trim($_GET['category'] ?? '');
        $sort = trim($_GET['sort'] ?? 'newest');
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = min(50, max(10, intval($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $conditions = ["r.status = 'Active'"];
        $params = [];

        if (!empty($search)) {
            $conditions[] = "(r.title LIKE ? OR r.organization_name LIKE ? OR r.summary LIKE ? OR r.advertisement_number LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        if (!empty($state) && $state !== 'ALL') {
            $conditions[] = "(r.state_code = ? OR r.state_code = 'ALL')";
            $params[] = $state;
        }

        if (!empty($qualification)) {
            $conditions[] = "r.qualification_level LIKE ?";
            $params[] = "%{$qualification}%";
        }

        if (!empty($category)) {
            $conditions[] = "r.organization_name LIKE ?";
            $params[] = "%{$category}%";
        }

        $whereClause = implode(' AND ', $conditions);

        // Sorting
        $orderBy = match ($sort) {
            'vacancies' => "r.total_vacancies DESC, r.updated_at DESC",
            'oldest' => "r.created_at ASC",
            default => "r.updated_at DESC"
        };

        // Count Total
        $countStmt = $this->db->prepare("SELECT COUNT(*) as total FROM recruitments r WHERE {$whereClause}");
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'] ?? 0;

        // Fetch Items
        $stmt = $this->db->prepare("
            SELECT r.*,
                   (SELECT event_date FROM recruitment_events WHERE recruitment_id = r.id AND event_type = 'APPLICATION_STARTED' LIMIT 1) as start_date,
                   (SELECT event_date FROM recruitment_events WHERE recruitment_id = r.id AND event_type = 'APPLICATION_CLOSED' LIMIT 1) as last_date,
                   (SELECT event_date FROM recruitment_events WHERE recruitment_id = r.id AND event_type = 'EXAM_DATE' LIMIT 1) as exam_date,
                   (SELECT claimed_value FROM fact_claims WHERE entity_type = 'Recruitment' AND entity_id = r.id AND field_name = 'Pay Scale' LIMIT 1) as pay_scale,
                   (SELECT claimed_value FROM fact_claims WHERE entity_type = 'Recruitment' AND entity_id = r.id AND field_name = 'Application Fee' LIMIT 1) as fee_details,
                   (SELECT claimed_value FROM fact_claims WHERE entity_type = 'Recruitment' AND entity_id = r.id AND field_name = 'Age Limit' LIMIT 1) as age_limit
            FROM recruitments r
            WHERE {$whereClause}
            ORDER BY {$orderBy}
            LIMIT {$limit} OFFSET {$offset}
        ");
        $stmt->execute($params);
        $jobs = $stmt->fetchAll();

        // Calculate dynamic urgency and formatted labels for each job
        foreach ($jobs as &$job) {
            $job['urgency_badge'] = '🟢 Active Opening';
            $job['urgency_class'] = 'badge-active';
            
            if (!empty($job['last_date'])) {
                $lastTimestamp = strtotime($job['last_date']);
                $nowTimestamp = time();
                $diffDays = ceil(($lastTimestamp - $nowTimestamp) / 86400);

                if ($diffDays < 0) {
                    $job['urgency_badge'] = '⌛ Registration Closed';
                    $job['urgency_class'] = 'badge-closed';
                } elseif ($diffDays <= 7) {
                    $job['urgency_badge'] = "🔥 Ending Soon ({$diffDays} Days Left)";
                    $job['urgency_class'] = 'badge-urgent';
                } else {
                    $job['urgency_badge'] = "⚡ Apply by " . date('d M Y', $lastTimestamp);
                    $job['urgency_class'] = 'badge-active';
                }
            }
        }

        echo json_encode([
            'success' => true,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit),
            'data' => $jobs
        ]);
    }

    public function getJobBySlug(string $slug): void {
        $stmt = $this->db->prepare("SELECT * FROM recruitments WHERE slug = ? LIMIT 1");
        $stmt->execute([$slug]);
        $rec = $stmt->fetch();

        if (!$rec) {
            // Fallback match
            $stmt = $this->db->prepare("SELECT * FROM recruitments WHERE title LIKE ? LIMIT 1");
            $stmt->execute(["%{$slug}%"]);
            $rec = $stmt->fetch();
        }

        if (!$rec) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Recruitment not found']);
            return;
        }

        $recId = $rec['id'];

        // Fetch Timeline Events
        $eventStmt = $this->db->prepare("SELECT * FROM recruitment_events WHERE recruitment_id = ? ORDER BY event_date ASC, id ASC");
        $eventStmt->execute([$recId]);
        $events = $eventStmt->fetchAll();

        // Fetch Facts
        $factStmt = $this->db->prepare("SELECT * FROM fact_claims WHERE entity_type = 'Recruitment' AND entity_id = ?");
        $factStmt->execute([$recId]);
        $facts = $factStmt->fetchAll();

        // Fetch SEO Metadata
        $seoStmt = $this->db->prepare("SELECT * FROM seo_metadata WHERE entity_type = 'Job' AND entity_id = ? LIMIT 1");
        $seoStmt->execute([$recId]);
        $seo = $seoStmt->fetch();

        // Fetch Related Articles
        $artStmt = $this->db->prepare("SELECT id, title, slug, article_type, reading_time_minutes FROM articles WHERE recruitment_id = ? AND status = 'Published' LIMIT 4");
        $artStmt->execute([$recId]);
        $articles = $artStmt->fetchAll();

        // Fetch Related Exam Hub
        $examStmt = $this->db->prepare("SELECT * FROM exams WHERE conducting_body LIKE ? OR name LIKE ? LIMIT 1");
        $examStmt->execute(["%{$rec['organization_name']}%", "%{$rec['title']}%"]);
        $relatedExam = $examStmt->fetch();

        echo json_encode([
            'success' => true,
            'recruitment' => $rec,
            'timeline' => $events,
            'facts' => $facts,
            'seo' => $seo,
            'related_articles' => $articles,
            'related_exam' => $relatedExam
        ]);
    }
}
