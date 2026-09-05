<?php
namespace App\Controllers;

use App\Database;
use PDO;

class AdminController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Check if request is authenticated via internal worker secret
     */
    public static function isWorkerAuthenticated(): bool {
        $expectedSecret = getenv('INTERNAL_API_SECRET') ?: ($_ENV['INTERNAL_API_SECRET'] ?? '');
        if (empty($expectedSecret)) {
            return false;
        }

        $providedSecret = $_SERVER['HTTP_X_INTERNAL_SECRET'] ?? '';
        if (empty($providedSecret) && isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $parts = explode(' ', $_SERVER['HTTP_AUTHORIZATION']);
            if (count($parts) === 2 && $parts[0] === 'Bearer') {
                $providedSecret = $parts[1];
            }
        }

        return !empty($providedSecret) && hash_equals($expectedSecret, $providedSecret);
    }

    /**
     * Check if currently logged in user is an authorized admin or internal worker
     */
    public static function isAuthenticated(): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!empty($_SESSION['admin_user']) && ($_SESSION['admin_user']['role'] ?? '') === 'ADMIN') {
            return true;
        }

        return self::isWorkerAuthenticated();
    }

    /**
     * Verify CSRF security token for state-changing admin requests
     */
    public static function verifyCsrf(): bool {
        // Internal worker requests authenticated via secret bypass CSRF
        if (self::isWorkerAuthenticated()) {
            return true;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $sessionToken = $_SESSION['csrf_token'] ?? '';
        if (empty($sessionToken)) {
            return false;
        }

        $providedToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (empty($providedToken)) {
            $input = json_decode(file_get_contents('php://input'), true);
            $providedToken = $input['csrf_token'] ?? ($_POST['csrf_token'] ?? '');
        }

        return !empty($providedToken) && hash_equals($sessionToken, $providedToken);
    }

    /**
     * Enforce authentication guard for admin APIs
     */
    public function requireAuth(): void {
        if (!self::isAuthenticated()) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'Unauthorized: Admin authentication session required.'
            ]);
            exit;
        }
    }

    /**
     * Get validated client IP address
     */
    private function getClientIp(): string {
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] 
            ?? $_SERVER['HTTP_X_FORWARDED_FOR'] 
            ?? $_SERVER['REMOTE_ADDR'] 
            ?? '127.0.0.1';
        if (str_contains($ip, ',')) {
            $ip = trim(explode(',', $ip)[0]);
        }
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '127.0.0.1';
    }

    /**
     * Process Admin Login with Brute-Force Rate Limiting & Session Regeneration
     */
    public function login(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $clientIp = $this->getClientIp();

        // 1. Cleanup expired attempts (> 15 minutes)
        try {
            $this->db->query("DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL 15 MINUTE)");
        } catch (\Throwable $e) {}

        // 2. Check current failed attempts for this IP
        $stmtCount = $this->db->prepare("
            SELECT COUNT(*) as fail_count 
            FROM login_attempts 
            WHERE ip_address = ? AND attempted_at >= (NOW() - INTERVAL 15 MINUTE)
        ");
        $stmtCount->execute([$clientIp]);
        $failCount = (int)$stmtCount->fetchColumn();

        if ($failCount >= 5) {
            http_response_code(429);
            echo json_encode([
                'success' => false,
                'error' => 'Too many failed login attempts. Your IP has been temporarily locked out for 15 minutes.'
            ]);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $identity = trim($input['identity'] ?? ($input['email'] ?? ($input['username'] ?? '')));
        $password = $input['password'] ?? '';

        if (empty($identity) || empty($password)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Email/Username and Password are required']);
            return;
        }

        $stmt = $this->db->prepare("
            SELECT id, username, email, password_hash, role, is_active 
            FROM users 
            WHERE (email = ? OR username = ?) AND role = 'ADMIN'
            LIMIT 1
        ");
        $stmt->execute([$identity, $identity]);
        $user = $stmt->fetch();

        if (!$user) {
            // Record failed attempt
            try {
                $failStmt = $this->db->prepare("INSERT INTO login_attempts (ip_address) VALUES (?)");
                $failStmt->execute([$clientIp]);
            } catch (\Throwable $e) {}

            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Invalid admin credentials or unauthorized account.']);
            return;
        }

        if (!$user['is_active']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Admin account has been deactivated.']);
            return;
        }

        // Security Hardening: Exclusively authorize designated master admin email
        if (strtolower(trim($user['email'])) !== 'hamaritumhari786@gmail.com') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Access denied: Unauthorized admin account.']);
            return;
        }

        // Strictly verify password using cryptographic hash
        $isValid = password_verify($password, $user['password_hash']);
        if (!$isValid && str_starts_with($user['password_hash'], '$2b$')) {
            $hy = str_replace('$2b$', '$2y$', $user['password_hash']);
            $isValid = password_verify($password, $hy);
        }

        if (!$isValid) {
            // Record failed attempt
            try {
                $failStmt = $this->db->prepare("INSERT INTO login_attempts (ip_address) VALUES (?)");
                $failStmt->execute([$clientIp]);
            } catch (\Throwable $e) {}

            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Invalid email or password.']);
            return;
        }

        // Login success: Clear failed attempts for this IP
        try {
            $clearStmt = $this->db->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
            $clearStmt->execute([$clientIp]);
        } catch (\Throwable $e) {}

        // Regenerate session ID to prevent Session Fixation attacks
        session_regenerate_id(true);

        // Ensure fresh CSRF token
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        $_SESSION['admin_user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
            'logged_at' => date('Y-m-d H:i:s')
        ];

        echo json_encode([
            'success' => true,
            'message' => 'Admin authentication successful',
            'csrf_token' => $_SESSION['csrf_token'],
            'user' => [
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role']
            ],
            'redirect' => '/admin/dashboard'
        ]);
    }

    /**
     * Process Admin Logout
     */
    public function logout(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();

        if (!empty($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
            echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
        } else {
            header('Location: /admin/login');
            exit;
        }
    }

    /**
     * Fetch KPI metrics and telemetry for Admin Dashboard
     */
    public function getDashboardMetrics(): void {
        $this->requireAuth();

        $activeJobs = $this->db->query("SELECT COUNT(*) as c FROM recruitments WHERE status = 'Active'")->fetch()['c'] ?? 0;
        $totalExams = $this->db->query("SELECT COUNT(*) as c FROM exams WHERE is_active = 1")->fetch()['c'] ?? 0;
        $totalArticles = $this->db->query("SELECT COUNT(*) as c FROM articles WHERE status = 'Published'")->fetch()['c'] ?? 0;
        $totalSources = $this->db->query("SELECT COUNT(*) as c FROM source_registry WHERE status = 'Active'")->fetch()['c'] ?? 0;

        // Recent Crawl Runs
        $runsStmt = $this->db->query("
            SELECT cr.*, sr.source_name 
            FROM crawl_runs cr 
            JOIN source_registry sr ON cr.source_id = sr.id 
            ORDER BY cr.started_at DESC 
            LIMIT 6
        ");
        $recentRuns = $runsStmt->fetchAll();

        // Recent Ingested Recruitments
        $recStmt = $this->db->query("
            SELECT id, title, organization_name, total_vacancies, qualification_level, state_code, status, created_at, updated_at 
            FROM recruitments 
            ORDER BY updated_at DESC 
            LIMIT 10
        ");
        $recentRecruitments = $recStmt->fetchAll();

        echo json_encode([
            'success' => true,
            'metrics' => [
                'active_jobs' => $activeJobs,
                'total_exams' => $totalExams,
                'total_articles' => $totalArticles,
                'monitored_sources' => $totalSources,
                'crawler_health_percentage' => 98.4
            ],
            'recent_runs' => $recentRuns,
            'recent_recruitments' => $recentRecruitments
        ]);
    }

    /**
     * Trigger background automation tasks
     */
    public function triggerAutomation(): void {
        $this->requireAuth();

        $action = $_POST['action'] ?? ($_GET['action'] ?? 'crawl');
        $workspaceRoot = dirname(dirname(dirname(__DIR__)));

        $commandMap = [
            'crawl' => "python automation/live_ingestion_pipeline.py",
            'orchestrator' => "python automation/engine/orchestrator.py",
            'exams' => "python automation/intelligence/exam_engine.py",
            'sitemap' => "python automation/seo/sitemap_generator.py"
        ];

        $cmd = $commandMap[$action] ?? "python automation/live_ingestion_pipeline.py";
        
        $output = [];
        $returnVar = 0;
        exec("cd /d \"{$workspaceRoot}\" && {$cmd} 2>&1", $output, $returnVar);

        echo json_encode([
            'success' => ($returnVar === 0),
            'action' => $action,
            'command' => $cmd,
            'output' => implode("\n", array_slice($output, -30)),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * List all monitored source registries
     */
    public function listSources(): void {
        $this->requireAuth();
        $stmt = $this->db->query("SELECT * FROM source_registry ORDER BY priority DESC, source_name ASC");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    }

    /**
     * Update Recruitment status
     */
    public function updateJobStatus(): void {
        $this->requireAuth();
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $jobId = intval($input['id'] ?? 0);
        $status = $input['status'] ?? 'Active';

        $validStatuses = ['Upcoming', 'Active', 'Exam_Phase', 'Result_Declared', 'Completed', 'Cancelled', 'Archived'];
        if (!in_array($status, $validStatuses, true)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid recruitment status']);
            return;
        }

        $stmt = $this->db->prepare("UPDATE recruitments SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $jobId]);

        echo json_encode([
            'success' => true,
            'message' => "Recruitment #{$jobId} status updated to '{$status}'"
        ]);
    }

    /**
     * Create New Recruitment / Job
     */
    public function createJob(): void {
        $this->requireAuth();
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $title = trim($input['title'] ?? '');
        $org = trim($input['organization_name'] ?? 'Government of India');
        $advt = trim($input['advertisement_number'] ?? '');
        $vacancies = intval($input['total_vacancies'] ?? 0);
        $qual = trim($input['qualification_level'] ?? 'Graduate Degree');
        $state = trim($input['state_code'] ?? 'ALL');
        $summary = trim($input['summary'] ?? '');
        $applyUrl = trim($input['official_apply_url'] ?? 'https://gov.in');
        $pdfUrl = trim($input['primary_notification_url'] ?? 'https://gov.in');
        $webUrl = trim($input['official_website_url'] ?? 'https://gov.in');
        $payScale = trim($input['pay_scale'] ?? 'As per 7th CPC Matrix');
        $ageLimit = trim($input['age_limit'] ?? '18 to 32 Years');
        $feeDetails = trim($input['fee_details'] ?? 'Gen/OBC: ₹100 | SC/ST: ₹0');
        $startDate = trim($input['start_date'] ?? date('Y-m-d'));
        $lastDate = trim($input['last_date'] ?? date('Y-m-d', strtotime('+30 days')));

        if (empty($title)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Recruitment title is required']);
            return;
        }

        // Generate slug and UUID
        $rawSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', "{$org}-{$title}-" . date('Y'))));
        $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));

        $stmt = $this->db->prepare("
            INSERT INTO recruitments (
                recruitment_uuid, title, slug, organization_name, advertisement_number,
                year, total_vacancies, status, primary_notification_url, official_website_url,
                official_apply_url, state_code, qualification_level, summary, is_verified, verified_at, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'Active', ?, ?, ?, ?, ?, ?, 1, NOW(), NOW(), NOW())
        ");
        $stmt->execute([
            $uuid, $title, $rawSlug, $org, $advt,
            intval(date('Y')), $vacancies, $pdfUrl, $webUrl,
            $applyUrl, $state, $qual, $summary
        ]);
        $jobId = $this->db->lastInsertId();

        // Insert Fact Claims
        $factStmt = $this->db->prepare("INSERT INTO fact_claims (entity_type, entity_id, field_name, claimed_value, confidence_score, verified_by) VALUES ('Recruitment', ?, ?, ?, 1.00, 'Admin Center')");
        $factStmt->execute([$jobId, 'Pay Scale', $payScale]);
        $factStmt->execute([$jobId, 'Age Limit', $ageLimit]);
        $factStmt->execute([$jobId, 'Application Fee', $feeDetails]);

        // Insert Timeline Events
        $evStmt = $this->db->prepare("INSERT INTO recruitment_events (recruitment_id, event_type, event_title, event_date, is_tentative, reference_url) VALUES (?, ?, ?, ?, 0, ?)");
        $evStmt->execute([$jobId, 'APPLICATION_STARTED', 'Application Window Opens', $startDate, $applyUrl]);
        $evStmt->execute([$jobId, 'APPLICATION_CLOSED', 'Last Date to Apply Online', $lastDate, $applyUrl]);

        echo json_encode([
            'success' => true,
            'message' => "Successfully created official recruitment #{$jobId}",
            'id' => $jobId,
            'slug' => $rawSlug
        ]);
    }

    /**
     * Delete Recruitment Notification (with safe cascade cleanup)
     */
    public function deleteJob(): void {
        $this->requireAuth();
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $jobId = intval($input['id'] ?? 0);

        if ($jobId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Valid Recruitment ID required']);
            return;
        }

        try {
            $this->db->beginTransaction();

            // 1. Delete associated recruitment events
            $this->db->prepare("DELETE FROM recruitment_events WHERE recruitment_id = ?")->execute([$jobId]);

            // 2. Delete associated fact claims
            $this->db->prepare("DELETE FROM fact_claims WHERE entity_type = 'Recruitment' AND entity_id = ?")->execute([$jobId]);

            // 3. Nullify references in cutoff_records
            $this->db->prepare("UPDATE cutoff_records SET recruitment_id = NULL WHERE recruitment_id = ?")->execute([$jobId]);

            // 4. Nullify references in articles
            $this->db->prepare("UPDATE articles SET recruitment_id = NULL WHERE recruitment_id = ?")->execute([$jobId]);

            // 5. Delete parent recruitment
            $stmt = $this->db->prepare("DELETE FROM recruitments WHERE id = ?");
            $stmt->execute([$jobId]);

            $this->db->commit();

            echo json_encode([
                'success' => true,
                'message' => "Recruitment notification #{$jobId} deleted successfully"
            ]);
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        }
    }

    /**
     * Create New Exam Hub
     */
    public function createExam(): void {
        $this->requireAuth();
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $name = trim($input['name'] ?? '');
        $shortName = trim($input['short_name'] ?? '');
        $conductingBody = trim($input['conducting_body'] ?? '');
        $category = trim($input['category'] ?? 'Other');
        $overview = trim($input['overview'] ?? '');
        $eligibility = trim($input['eligibility_summary'] ?? '');
        $ageLimit = trim($input['age_limit_summary'] ?? '');
        $website = trim($input['official_website'] ?? 'https://gov.in');

        if (empty($name) || empty($shortName)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Exam name and short name are required']);
            return;
        }

        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $shortName)));
        $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));

        $stmt = $this->db->prepare("
            INSERT INTO exams (exam_uuid, name, short_name, slug, conducting_body, category, frequency, overview, eligibility_summary, age_limit_summary, official_website, is_active, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, 'Annual', ?, ?, ?, ?, 1, NOW(), NOW())
        ");
        $stmt->execute([$uuid, $name, $shortName, $slug, $conductingBody, $category, $overview, $eligibility, $ageLimit, $website]);

        echo json_encode([
            'success' => true,
            'message' => "Successfully created Exam Hub: {$shortName}",
            'id' => $this->db->lastInsertId(),
            'slug' => $slug
        ]);
    }

    /**
     * Create New Guide Article
     */
    public function createArticle(): void {
        $this->requireAuth();
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $title = trim($input['title'] ?? '');
        $type = trim($input['article_type'] ?? 'Notification_Guide');
        $excerpt = trim($input['excerpt'] ?? '');
        $content = trim($input['content'] ?? '');
        $readingTime = max(1, intval($input['reading_time_minutes'] ?? 5));
        $qualityScore = min(100, max(80, intval($input['quality_score'] ?? 95)));
        $recId = !empty($input['recruitment_id']) ? intval($input['recruitment_id']) : null;

        if (empty($title) || empty($content)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Article title and content are required']);
            return;
        }

        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));

        $stmt = $this->db->prepare("
            INSERT INTO articles (article_uuid, title, slug, article_type, excerpt, content, reading_time_minutes, quality_score, recruitment_id, status, published_at, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Published', NOW(), NOW(), NOW())
        ");
        $stmt->execute([$uuid, $title, $slug, $type, $excerpt, $content, $readingTime, $qualityScore, $recId]);

        echo json_encode([
            'success' => true,
            'message' => "Successfully published Guide: {$title}",
            'id' => $this->db->lastInsertId(),
            'slug' => $slug
        ]);
    }

    /**
     * Register New Monitored Source
     */
    public function createSource(): void {
        $this->requireAuth();
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $sourceName = trim($input['source_name'] ?? '');
        $baseUrl = trim($input['base_url'] ?? '');
        $sourceType = trim($input['source_type'] ?? 'Other');
        $priority = intval($input['priority'] ?? 5);

        if (empty($sourceName) || empty($baseUrl)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Source name and base URL are required']);
            return;
        }

        $domain = parse_url($baseUrl, PHP_URL_HOST);
        if (!$domain) {
            $clean = preg_replace('#^https?://#i', '', $baseUrl);
            $domain = explode('/', $clean)[0] ?: 'official.gov.in';
        }
        $domain = strtolower($domain);

        $typeMap = [
            'Central' => 'UPSC',
            'State' => 'StatePSC',
            'Railway' => 'Railway',
            'Banking' => 'Bank',
            'Defence' => 'Defense',
            'Police' => 'Police',
            'Other' => 'Other'
        ];
        $enumType = $typeMap[$sourceType] ?? 'Other';

        $pNum = intval($priority);
        if ($pNum >= 8) {
            $enumPriority = 'Critical';
        } elseif ($pNum >= 6) {
            $enumPriority = 'High';
        } elseif ($pNum >= 4) {
            $enumPriority = 'Medium';
        } else {
            $enumPriority = 'Low';
        }

        $stmt = $this->db->prepare("
            INSERT INTO source_registry (
                source_name, domain, website_url, recruitment_url, source_type, priority, status, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, 'Active', NOW(), NOW())
            ON DUPLICATE KEY UPDATE 
                source_name = VALUES(source_name),
                website_url = VALUES(website_url),
                recruitment_url = VALUES(recruitment_url),
                source_type = VALUES(source_type),
                priority = VALUES(priority),
                updated_at = NOW()
        ");
        $stmt->execute([$sourceName, $domain, $baseUrl, $baseUrl, $enumType, $enumPriority]);

        echo json_encode([
            'success' => true,
            'message' => "Successfully registered monitored source: {$sourceName}",
            'id' => $this->db->lastInsertId()
        ]);
    }

    /**
     * Delete Monitored Source
     */
    public function deleteSource(): void {
        $this->requireAuth();
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $id = intval($input['id'] ?? 0);

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Valid Source ID required']);
            return;
        }

        $stmt = $this->db->prepare("DELETE FROM source_registry WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode([
            'success' => true,
            'message' => "Source #{$id} removed from monitored registry"
        ]);
    }

    /**
     * Fetch Single Recruitment Data for Editing
     */
    public function getJob(): void {
        $this->requireAuth();
        $jobId = intval($_GET['id'] ?? 0);

        if ($jobId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Valid Job ID required']);
            return;
        }

        $stmt = $this->db->prepare("SELECT * FROM recruitments WHERE id = ?");
        $stmt->execute([$jobId]);
        $job = $stmt->fetch();

        if (!$job) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Job not found']);
            return;
        }

        // Fetch Fact Claims
        $facts = $this->db->prepare("SELECT field_name, claimed_value FROM fact_claims WHERE entity_type = 'Recruitment' AND entity_id = ?");
        $facts->execute([$jobId]);
        $claims = [];
        while ($row = $facts->fetch()) {
            $claims[$row['field_name']] = $row['claimed_value'];
        }

        // Fetch Events
        $events = $this->db->prepare("SELECT event_type, event_date FROM recruitment_events WHERE recruitment_id = ?");
        $events->execute([$jobId]);
        $evMap = [];
        while ($row = $events->fetch()) {
            $evMap[$row['event_type']] = $row['event_date'];
        }

        $job['pay_scale'] = $claims['Pay Scale'] ?? '';
        $job['age_limit'] = $claims['Age Limit'] ?? '';
        $job['fee_details'] = $claims['Application Fee'] ?? '';
        $job['start_date'] = $evMap['APPLICATION_STARTED'] ?? '';
        $job['last_date'] = $evMap['APPLICATION_CLOSED'] ?? '';

        echo json_encode(['success' => true, 'data' => $job]);
    }

    /**
     * Update Recruitment Data
     */
    public function updateJob(): void {
        $this->requireAuth();
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $jobId = intval($input['id'] ?? 0);

        if ($jobId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Valid Job ID is required']);
            return;
        }

        $title = trim($input['title'] ?? '');
        $org = trim($input['organization_name'] ?? 'Government of India');
        $advt = trim($input['advertisement_number'] ?? '');
        $vacancies = intval($input['total_vacancies'] ?? 0);
        $qual = trim($input['qualification_level'] ?? 'Graduate Degree');
        $state = trim($input['state_code'] ?? 'ALL');
        $status = trim($input['status'] ?? 'Active');
        $summary = trim($input['summary'] ?? '');
        $applyUrl = trim($input['official_apply_url'] ?? 'https://gov.in');
        $pdfUrl = trim($input['primary_notification_url'] ?? 'https://gov.in');
        $payScale = trim($input['pay_scale'] ?? '');
        $ageLimit = trim($input['age_limit'] ?? '');
        $feeDetails = trim($input['fee_details'] ?? '');
        $startDate = trim($input['start_date'] ?? '');
        $lastDate = trim($input['last_date'] ?? '');

        if (empty($title)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Recruitment title is required']);
            return;
        }

        $stmt = $this->db->prepare("
            UPDATE recruitments SET 
                title = ?, organization_name = ?, advertisement_number = ?,
                total_vacancies = ?, status = ?, primary_notification_url = ?,
                official_apply_url = ?, state_code = ?, qualification_level = ?,
                summary = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $title, $org, $advt,
            $vacancies, $status, $pdfUrl,
            $applyUrl, $state, $qual,
            $summary, $jobId
        ]);

        // Upsert Fact Claims
        $this->db->prepare("DELETE FROM fact_claims WHERE entity_type = 'Recruitment' AND entity_id = ?")->execute([$jobId]);
        $factStmt = $this->db->prepare("INSERT INTO fact_claims (entity_type, entity_id, field_name, claimed_value, confidence_score, verified_by) VALUES ('Recruitment', ?, ?, ?, 1.00, 'Admin Center')");
        if (!empty($payScale)) $factStmt->execute([$jobId, 'Pay Scale', $payScale]);
        if (!empty($ageLimit)) $factStmt->execute([$jobId, 'Age Limit', $ageLimit]);
        if (!empty($feeDetails)) $factStmt->execute([$jobId, 'Application Fee', $feeDetails]);

        // Upsert Events
        $this->db->prepare("DELETE FROM recruitment_events WHERE recruitment_id = ?")->execute([$jobId]);
        $evStmt = $this->db->prepare("INSERT INTO recruitment_events (recruitment_id, event_type, event_title, event_date, is_tentative, reference_url) VALUES (?, ?, ?, ?, 0, ?)");
        if (!empty($startDate)) $evStmt->execute([$jobId, 'APPLICATION_STARTED', 'Application Window Opens', $startDate, $applyUrl]);
        if (!empty($lastDate)) $evStmt->execute([$jobId, 'APPLICATION_CLOSED', 'Last Date to Apply Online', $lastDate, $applyUrl]);

        echo json_encode([
            'success' => true,
            'message' => "Successfully updated recruitment #{$jobId}"
        ]);
    }


    /**
     * Fetch Single Exam Hub Data for Editing
     */
    public function getExam(): void {
        $this->requireAuth();
        $examId = intval($_GET['id'] ?? 0);

        if ($examId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Valid Exam ID required']);
            return;
        }

        $stmt = $this->db->prepare("SELECT * FROM exams WHERE id = ?");
        $stmt->execute([$examId]);
        $exam = $stmt->fetch();

        if (!$exam) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Exam Hub not found']);
            return;
        }

        echo json_encode(['success' => true, 'data' => $exam]);
    }

    /**
     * Update Exam Hub Data
     */
    public function updateExam(): void {
        $this->requireAuth();
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $examId = intval($input['id'] ?? 0);

        if ($examId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Valid Exam ID required']);
            return;
        }

        $name = trim($input['name'] ?? '');
        $shortName = trim($input['short_name'] ?? '');
        $conductingBody = trim($input['conducting_body'] ?? '');
        $category = trim($input['category'] ?? 'Other');
        $overview = trim($input['overview'] ?? '');
        $eligibility = trim($input['eligibility_summary'] ?? '');
        $ageLimit = trim($input['age_limit_summary'] ?? '');
        $website = trim($input['official_website'] ?? 'https://gov.in');
        $frequency = trim($input['frequency'] ?? 'Annual');
        $isActive = isset($input['is_active']) ? intval($input['is_active']) : 1;

        if (empty($name) || empty($shortName)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Exam name and short name are required']);
            return;
        }

        $stmt = $this->db->prepare("
            UPDATE exams SET 
                name = ?, short_name = ?, conducting_body = ?, category = ?,
                frequency = ?, overview = ?, eligibility_summary = ?, age_limit_summary = ?,
                official_website = ?, is_active = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$name, $shortName, $conductingBody, $category, $frequency, $overview, $eligibility, $ageLimit, $website, $isActive, $examId]);

        echo json_encode([
            'success' => true,
            'message' => "Successfully updated Exam Hub: {$shortName}"
        ]);
    }

    /**
     * Delete Exam Hub (with safe cascade cleanup)
     */
    public function deleteExam(): void {
        $this->requireAuth();
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $examId = intval($input['id'] ?? 0);

        if ($examId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Valid Exam ID required']);
            return;
        }

        try {
            $this->db->beginTransaction();

            // 1. Cascade cleanup child tables referencing exams
            $this->db->prepare("DELETE FROM cutoff_records WHERE exam_id = ?")->execute([$examId]);
            $this->db->prepare("DELETE FROM exam_patterns WHERE exam_id = ?")->execute([$examId]);
            $this->db->prepare("DELETE FROM exam_phases WHERE exam_id = ?")->execute([$examId]);
            $this->db->prepare("DELETE FROM exam_syllabus WHERE exam_id = ?")->execute([$examId]);
            $this->db->prepare("DELETE FROM question_bank WHERE exam_id = ?")->execute([$examId]);

            // 2. Delete parent exam
            $stmt = $this->db->prepare("DELETE FROM exams WHERE id = ?");
            $stmt->execute([$examId]);

            $this->db->commit();

            echo json_encode([
                'success' => true,
                'message' => "Exam Hub #{$examId} deleted successfully"
            ]);
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        }
    }

    /**
     * Fetch Single Article Data for Editing
     */
    public function getArticle(): void {
        $this->requireAuth();
        $artId = intval($_GET['id'] ?? 0);

        if ($artId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Valid Article ID required']);
            return;
        }

        $stmt = $this->db->prepare("SELECT * FROM articles WHERE id = ?");
        $stmt->execute([$artId]);
        $art = $stmt->fetch();

        if (!$art) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Article not found']);
            return;
        }

        echo json_encode(['success' => true, 'data' => $art]);
    }

    /**
     * Update Guide Article
     */
    public function updateArticle(): void {
        $this->requireAuth();
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $artId = intval($input['id'] ?? 0);

        if ($artId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Valid Article ID required']);
            return;
        }

        $title = trim($input['title'] ?? '');
        $type = trim($input['article_type'] ?? 'Notification_Guide');
        $excerpt = trim($input['excerpt'] ?? '');
        $content = trim($input['content'] ?? '');
        $readingTime = max(1, intval($input['reading_time_minutes'] ?? 5));
        $qualityScore = min(100, max(80, intval($input['quality_score'] ?? 95)));
        $recId = !empty($input['recruitment_id']) ? intval($input['recruitment_id']) : null;
        $status = trim($input['status'] ?? 'Published');

        if (empty($title) || empty($content)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Article title and content are required']);
            return;
        }

        $stmt = $this->db->prepare("
            UPDATE articles SET 
                title = ?, article_type = ?, excerpt = ?, content = ?,
                reading_time_minutes = ?, quality_score = ?, recruitment_id = ?,
                status = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$title, $type, $excerpt, $content, $readingTime, $qualityScore, $recId, $status, $artId]);

        echo json_encode([
            'success' => true,
            'message' => "Successfully updated Guide: {$title}"
        ]);
    }

    /**
     * Delete Guide Article (with safe cascade cleanup)
     */
    public function deleteArticle(): void {
        $this->requireAuth();
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $artId = intval($input['id'] ?? 0);

        if ($artId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Valid Article ID required']);
            return;
        }

        try {
            $this->db->beginTransaction();

            // 1. Delete article versions first
            $this->db->prepare("DELETE FROM article_versions WHERE article_id = ?")->execute([$artId]);

            // 2. Delete parent article
            $stmt = $this->db->prepare("DELETE FROM articles WHERE id = ?");
            $stmt->execute([$artId]);

            $this->db->commit();

            echo json_encode([
                'success' => true,
                'message' => "Guide Article #{$artId} deleted successfully"
            ]);
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        }
    }

    /**
     * Fetch Single Commission Data for Editing
     */
    public function getCommission(): void {
        $this->requireAuth();
        $id = intval($_GET['id'] ?? 0);

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Valid Commission ID required']);
            return;
        }

        $stmt = $this->db->prepare("SELECT * FROM commissions WHERE id = ?");
        $stmt->execute([$id]);
        $comm = $stmt->fetch();

        if (!$comm) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Commission not found']);
            return;
        }

        echo json_encode(['success' => true, 'data' => $comm]);
    }

    /**
     * Create New Commission
     */
    public function createCommission(): void {
        $this->requireAuth();
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $name = trim($input['name'] ?? '');
        $shortName = trim($input['short_name'] ?? '');
        $slug = trim($input['slug'] ?? '');
        $emblem = trim($input['emblem'] ?? 'landmark');
        $hq = trim($input['hq'] ?? '');
        $website = trim($input['website'] ?? 'https://gov.in');
        $otrUrl = trim($input['otr_url'] ?? '');
        $category = trim($input['category'] ?? 'Central & State Services');
        $description = trim($input['description'] ?? '');
        $annualCandidates = trim($input['annual_candidates'] ?? '1 Million+ Aspirants');
        $selectionPhases = trim($input['selection_phases'] ?? 'Prelims -> Mains -> Interview');
        $filterKeyword = trim($input['filter_keyword'] ?? $shortName);
        $isActive = isset($input['is_active']) ? intval($input['is_active']) : 1;

        if (empty($name) || empty($shortName)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Commission name and short name are required']);
            return;
        }

        if (empty($slug)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $shortName)));
        } else {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $slug)));
        }

        if (empty($slug)) {
            $slug = 'commission-' . time();
        }

        // Automatic slug collision resolver (prevents duplicate key errors)
        $baseSlug = $slug;
        $counter = 1;
        while (true) {
            $checkStmt = $this->db->prepare("SELECT id FROM commissions WHERE slug = ? LIMIT 1");
            $checkStmt->execute([$slug]);
            if (!$checkStmt->fetch()) {
                break;
            }
            $counter++;
            $slug = "{$baseSlug}-{$counter}";
        }

        $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));

        try {
            $stmt = $this->db->prepare("
                INSERT INTO commissions (
                    commission_uuid, name, short_name, slug, emblem, hq, website, otr_url, category, description, annual_candidates, selection_phases, filter_keyword, is_active, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([
                $uuid, $name, $shortName, $slug, $emblem, $hq, $website, $otrUrl, $category, $description, $annualCandidates, $selectionPhases, $filterKeyword, $isActive
            ]);

            echo json_encode([
                'success' => true,
                'message' => "Successfully created Commission: {$shortName}",
                'id' => $this->db->lastInsertId(),
                'slug' => $slug
            ]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        }
    }

    /**
     * Update Commission
     */
    public function updateCommission(): void {
        $this->requireAuth();
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $id = intval($input['id'] ?? 0);

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Valid Commission ID required']);
            return;
        }

        $name = trim($input['name'] ?? '');
        $shortName = trim($input['short_name'] ?? '');
        $slug = trim($input['slug'] ?? '');
        $emblem = trim($input['emblem'] ?? '🏛️');
        $hq = trim($input['hq'] ?? '');
        $website = trim($input['website'] ?? 'https://gov.in');
        $otrUrl = trim($input['otr_url'] ?? '');
        $category = trim($input['category'] ?? '');
        $description = trim($input['description'] ?? '');
        $annualCandidates = trim($input['annual_candidates'] ?? '');
        $selectionPhases = trim($input['selection_phases'] ?? '');
        $filterKeyword = trim($input['filter_keyword'] ?? $shortName);
        $isActive = isset($input['is_active']) ? intval($input['is_active']) : 1;

        if (empty($name) || empty($shortName)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Commission name and short name are required']);
            return;
        }

        if (empty($slug)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $shortName)));
        }

        $stmt = $this->db->prepare("
            UPDATE commissions SET 
                name = ?, short_name = ?, slug = ?, emblem = ?, hq = ?, website = ?,
                otr_url = ?, category = ?, description = ?, annual_candidates = ?,
                selection_phases = ?, filter_keyword = ?, is_active = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $name, $shortName, $slug, $emblem, $hq, $website, $otrUrl, $category,
            $description, $annualCandidates, $selectionPhases, $filterKeyword, $isActive, $id
        ]);

        echo json_encode([
            'success' => true,
            'message' => "Successfully updated Commission: {$shortName}"
        ]);
    }

    /**
     * Delete Commission
     */
    public function deleteCommission(): void {
        $this->requireAuth();
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $id = intval($input['id'] ?? 0);

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Valid Commission ID required']);
            return;
        }

        $stmt = $this->db->prepare("DELETE FROM commissions WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode([
            'success' => true,
            'message' => "Commission #{$id} deleted successfully"
        ]);
    }

    /**
     * Fetch Single Event (Admit Card / Result / Notice)
     */
    public function getEvent(): void {
        $this->requireAuth();
        $id = intval($_GET['id'] ?? 0);

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Valid Event ID required']);
            return;
        }

        $stmt = $this->db->prepare("
            SELECT re.*, r.title as recruitment_title, r.organization_name as rec_org_name
            FROM recruitment_events re
            LEFT JOIN recruitments r ON re.recruitment_id = r.id
            WHERE re.id = ?
        ");
        $stmt->execute([$id]);
        $event = $stmt->fetch();

        if (!$event) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Event record not found']);
            return;
        }

        echo json_encode(['success' => true, 'data' => $event]);
    }

    /**
     * Create New Event (Admit Card / Result / Timeline Notice)
     */
    public function createEvent(): void {
        $this->requireAuth();
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $recId = !empty($input['recruitment_id']) ? intval($input['recruitment_id']) : null;
        $orgName = trim($input['organization_name'] ?? '');
        $eventType = trim($input['event_type'] ?? 'ADMIT_CARD_RELEASED');
        $eventTitle = trim($input['event_title'] ?? '');
        $status = trim($input['status'] ?? 'RELEASED');
        $eventDate = !empty($input['event_date']) ? trim($input['event_date']) : date('Y-m-d');
        $isTentative = !empty($input['is_tentative']) ? 1 : 0;
        $details = trim($input['details'] ?? '');
        $refUrl = trim($input['reference_url'] ?? 'https://gov.in');

        if (empty($eventTitle)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Event title is required']);
            return;
        }

        if ($recId && empty($orgName)) {
            $stmtR = $this->db->prepare("SELECT organization_name FROM recruitments WHERE id = ?");
            $stmtR->execute([$recId]);
            $orgName = $stmtR->fetch()['organization_name'] ?? 'Government of India';
        }

        $stmt = $this->db->prepare("
            INSERT INTO recruitment_events (
                recruitment_id, organization_name, event_type, event_title, status, event_date, is_tentative, details, reference_url, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$recId, $orgName, $eventType, $eventTitle, $status, $eventDate, $isTentative, $details, $refUrl]);

        echo json_encode([
            'success' => true,
            'message' => "Successfully created event: {$eventTitle}",
            'id' => $this->db->lastInsertId()
        ]);
    }

    /**
     * Update Event
     */
    public function updateEvent(): void {
        $this->requireAuth();
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $id = intval($input['id'] ?? 0);

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Valid Event ID required']);
            return;
        }

        $recId = !empty($input['recruitment_id']) ? intval($input['recruitment_id']) : null;
        $orgName = trim($input['organization_name'] ?? '');
        $eventType = trim($input['event_type'] ?? 'ADMIT_CARD_RELEASED');
        $eventTitle = trim($input['event_title'] ?? '');
        $status = trim($input['status'] ?? 'RELEASED');
        $eventDate = !empty($input['event_date']) ? trim($input['event_date']) : date('Y-m-d');
        $isTentative = !empty($input['is_tentative']) ? 1 : 0;
        $details = trim($input['details'] ?? '');
        $refUrl = trim($input['reference_url'] ?? 'https://gov.in');

        if (empty($eventTitle)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Event title is required']);
            return;
        }

        if ($recId && empty($orgName)) {
            $stmtR = $this->db->prepare("SELECT organization_name FROM recruitments WHERE id = ?");
            $stmtR->execute([$recId]);
            $orgName = $stmtR->fetch()['organization_name'] ?? 'Government of India';
        }

        $stmt = $this->db->prepare("
            UPDATE recruitment_events SET 
                recruitment_id = ?, organization_name = ?, event_type = ?,
                event_title = ?, status = ?, event_date = ?, is_tentative = ?,
                details = ?, reference_url = ?
            WHERE id = ?
        ");
        $stmt->execute([$recId, $orgName, $eventType, $eventTitle, $status, $eventDate, $isTentative, $details, $refUrl, $id]);

        echo json_encode([
            'success' => true,
            'message' => "Successfully updated event: {$eventTitle}"
        ]);
    }

    /**
     * Inline Update Event Status (Admit Cards / Results / Milestones)
     */
    public function updateEventStatus(): void {
        $this->requireAuth();
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $id = intval($input['id'] ?? 0);
        $status = trim($input['status'] ?? 'RELEASED');

        $validStatuses = ['RELEASED', 'EXPECTED', 'CITY_SLIP', 'POSTPONED', 'DECLARED', 'PROVISIONAL_KEY', 'FINAL_LIST'];
        if (!in_array($status, $validStatuses, true)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid event status']);
            return;
        }

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Valid event ID required']);
            return;
        }

        $stmt = $this->db->prepare("UPDATE recruitment_events SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);

        echo json_encode([
            'success' => true,
            'message' => "Event #{$id} status updated to '{$status}'"
        ]);
    }

    /**
     * Delete Event
     */
    public function deleteEvent(): void {
        $this->requireAuth();
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $id = intval($input['id'] ?? 0);

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Valid Event ID required']);
            return;
        }

        $stmt = $this->db->prepare("DELETE FROM recruitment_events WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode([
            'success' => true,
            'message' => "Event #{$id} deleted successfully"
        ]);
    }

    /**
     * Create Cutoff Record
     */
    public function createCutoff(): void {
        $this->requireAuth();
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $examId = intval($input['exam_id'] ?? 0);
        $recId = !empty($input['recruitment_id']) ? intval($input['recruitment_id']) : null;
        $year = intval($input['year'] ?? date('Y'));
        $category = trim($input['category'] ?? 'UR');
        $cutoffMarks = floatval($input['cutoff_marks'] ?? 0);
        $totalMarks = floatval($input['total_marks'] ?? 200);
        $candidates = !empty($input['qualifying_candidates']) ? intval($input['qualifying_candidates']) : null;
        $url = trim($input['official_notice_url'] ?? 'https://gov.in');
        $notes = trim($input['notes'] ?? '');

        if ($examId <= 0 || $cutoffMarks <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Exam selection and valid Cutoff marks are required']);
            return;
        }

        $stmt = $this->db->prepare("
            INSERT INTO cutoff_records (exam_id, recruitment_id, year, category, cutoff_marks, total_marks, qualifying_candidates, official_notice_url, notes, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$examId, $recId, $year, $category, $cutoffMarks, $totalMarks, $candidates, $url, $notes]);

        echo json_encode([
            'success' => true,
            'message' => "Successfully added Cutoff score: {$cutoffMarks}/{$totalMarks} ({$category})",
            'id' => $this->db->lastInsertId()
        ]);
    }

    /**
     * Fetch Single Cutoff Record for Editing
     */
    public function getCutoff(): void {
        $this->requireAuth();
        $id = intval($_GET['id'] ?? 0);

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Valid Cutoff ID required']);
            return;
        }

        $stmt = $this->db->prepare("
            SELECT c.*, e.name as exam_name
            FROM cutoff_records c
            LEFT JOIN exams e ON c.exam_id = e.id
            WHERE c.id = ?
        ");
        $stmt->execute([$id]);
        $cutoff = $stmt->fetch();

        if (!$cutoff) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Cutoff record not found']);
            return;
        }

        echo json_encode(['success' => true, 'data' => $cutoff]);
    }

    /**
     * Update Cutoff Record
     */
    public function updateCutoff(): void {
        $this->requireAuth();
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $id = intval($input['id'] ?? 0);

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Valid Cutoff ID required']);
            return;
        }

        $examId = intval($input['exam_id'] ?? 0);
        $recId = !empty($input['recruitment_id']) ? intval($input['recruitment_id']) : null;
        $year = intval($input['year'] ?? date('Y'));
        $category = trim($input['category'] ?? 'UR');
        $cutoffMarks = floatval($input['cutoff_marks'] ?? 0);
        $totalMarks = floatval($input['total_marks'] ?? 200);
        $candidates = !empty($input['qualifying_candidates']) ? intval($input['qualifying_candidates']) : null;
        $url = trim($input['official_notice_url'] ?? 'https://gov.in');
        $notes = trim($input['notes'] ?? '');

        if ($examId <= 0 || $cutoffMarks <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Exam selection and valid Cutoff marks are required']);
            return;
        }

        $stmt = $this->db->prepare("
            UPDATE cutoff_records SET
                exam_id = ?, recruitment_id = ?, year = ?, category = ?,
                cutoff_marks = ?, total_marks = ?, qualifying_candidates = ?,
                official_notice_url = ?, notes = ?
            WHERE id = ?
        ");
        $stmt->execute([$examId, $recId, $year, $category, $cutoffMarks, $totalMarks, $candidates, $url, $notes, $id]);

        echo json_encode([
            'success' => true,
            'message' => "Cutoff record #{$id} updated successfully"
        ]);
    }

    /**
     * Delete Cutoff Record
     */
    public function deleteCutoff(): void {
        $this->requireAuth();
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $id = intval($input['id'] ?? 0);

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Valid Cutoff ID required']);
            return;
        }

        $stmt = $this->db->prepare("DELETE FROM cutoff_records WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode([
            'success' => true,
            'message' => "Cutoff record #{$id} deleted successfully"
        ]);
    }

    /**
     * Get Autonomous 4-Hour Daemon Status
     */
    public function getDaemonStatus(): void {
        $this->requireAuth();
        $workspaceRoot = dirname(dirname(dirname(__DIR__)));
        $stateFile = $workspaceRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'daemon_state.json';

        $state = [
            'status' => 'STOPPED',
            'pid' => null,
            'interval_hours' => 4,
            'started_at' => null,
            'last_run_at' => null,
            'next_run_at' => null,
            'last_run_status' => null
        ];

        if (file_exists($stateFile)) {
            $content = file_get_contents($stateFile);
            $parsed = json_decode($content, true);
            if (is_array($parsed)) {
                $state = array_merge($state, $parsed);
            }
        }

        // Double check PID if marked RUNNING
        if ($state['status'] === 'RUNNING' && !empty($state['pid'])) {
            $pid = intval($state['pid']);
            $isAlive = false;
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $chk = [];
                exec("tasklist /FI \"PID eq {$pid}\" /FO CSV /NH", $chk);
                $isAlive = !empty($chk) && str_contains(implode(" ", $chk), (string)$pid);
            } else {
                $isAlive = file_exists("/proc/{$pid}");
            }
            if (!$isAlive) {
                $state['status'] = 'STOPPED';
                $state['pid'] = null;
            }
        }

        echo json_encode(['success' => true, 'data' => $state]);
    }

    /**
     * Start / Stop Autonomous 4-Hour Daemon
     */
    public function toggleDaemon(): void {
        $this->requireAuth();
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $action = trim($input['action'] ?? 'status');
        $workspaceRoot = dirname(dirname(dirname(__DIR__)));

        if ($action === 'start') {
            $out = [];
            exec("cd /d \"{$workspaceRoot}\" && python automation/scheduler/cron.py --start 2>&1", $out);
            $this->getDaemonStatus();
            return;
        } elseif ($action === 'stop') {
            $out = [];
            exec("cd /d \"{$workspaceRoot}\" && python automation/scheduler/cron.py --stop 2>&1", $out);
            $this->getDaemonStatus();
            return;
        }

        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Action must be start or stop']);
    }

    /**
     * Fetch Audit Log of Automation Runs from MySQL
     */
    public function getAutomationRuns(): void {
        $this->requireAuth();
        $stmt = $this->db->query("
            SELECT id, run_uuid, stage_name, trigger_source, status,
                   notices_found, new_ingested, skipped_unchanged,
                   quarantined_count AS quarantined,
                   execution_time_seconds AS elapsed_seconds,
                   started_at, completed_at
            FROM automation_runs
            ORDER BY id DESC
            LIMIT 20
        ");
        $runs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $runs]);
    }

    /**
     * Fetch Quarantined Notices Requiring Verification
     */
    public function getReviewQueue(): void {
        $this->requireAuth();
        $stmt = $this->db->query("
            SELECT id, title, organization_name, total_vacancies, qualification_level,
                   official_apply_url, primary_notification_url, review_status, anomaly_flags,
                   created_at, updated_at
            FROM recruitments
            WHERE review_status = 'REVIEW_PENDING'
            ORDER BY id DESC
            LIMIT 50
        ");
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $items]);
    }

    /**
     * Approve Quarantined Notice
     */
    public function approveReviewItem(): void {
        $this->requireAuth();
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $id = intval($input['id'] ?? ($_POST['id'] ?? ($_GET['id'] ?? 0)));

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Valid Recruitment ID required']);
            return;
        }

        // Fetch recruitment title
        $fetchStmt = $this->db->prepare("SELECT title FROM recruitments WHERE id = ?");
        $fetchStmt->execute([$id]);
        $rec = $fetchStmt->fetch();

        if (!$rec) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => "Recruitment #{$id} not found"]);
            return;
        }

        $stmt = $this->db->prepare("
            UPDATE recruitments 
            SET review_status = 'VERIFIED', status = 'Active', updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$id]);

        // Synchronize corresponding public job posting if present
        try {
            $stmtJob = $this->db->prepare("
                UPDATE jobs 
                SET status = 'OPEN', updated_at = NOW() 
                WHERE title = ?
            ");
            $stmtJob->execute([$rec['title']]);
        } catch (\Throwable $e) {}

        echo json_encode([
            'success' => true,
            'message' => "Recruitment #{$id} ({$rec['title']}) approved and published to live portal"
        ]);
    }

    /**
     * Reject Quarantined Notice
     */
    public function rejectReviewItem(): void {
        $this->requireAuth();
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $id = intval($input['id'] ?? ($_POST['id'] ?? ($_GET['id'] ?? 0)));

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Valid Recruitment ID required']);
            return;
        }

        // Fetch recruitment title
        $fetchStmt = $this->db->prepare("SELECT title FROM recruitments WHERE id = ?");
        $fetchStmt->execute([$id]);
        $rec = $fetchStmt->fetch();

        if (!$rec) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => "Recruitment #{$id} not found"]);
            return;
        }

        $stmt = $this->db->prepare("
            UPDATE recruitments 
            SET review_status = 'REJECTED', status = 'Cancelled', updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$id]);

        // Close corresponding public job posting if present
        try {
            $stmtJob = $this->db->prepare("
                UPDATE jobs 
                SET status = 'CLOSED', updated_at = NOW() 
                WHERE title = ?
            ");
            $stmtJob->execute([$rec['title']]);
        } catch (\Throwable $e) {}

        echo json_encode([
            'success' => true,
            'message' => "Recruitment #{$id} ({$rec['title']}) rejected and archived"
        ]);
    }
}


