import subprocess
import json

test_runner_php = """<?php
require_once __DIR__ . '/../backend/app/Database.php';
require_once __DIR__ . '/../backend/app/Controllers/AdminController.php';

use App\Database;
use App\Controllers\AdminController;

if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION['admin_user'] = ['username' => 'TestAdmin', 'email' => 'admin@govrecruit.ai', 'role' => 'ADMIN'];

$controller = new AdminController();
$db = Database::getConnection();

$report = [];

// ==========================================
// 1. RECRUITMENT CRUD TEST
// ==========================================
try {
    // 1.1 CREATE
    $_POST = [
        'title' => 'CRUD Test Specialist Officer 2026',
        'organization_name' => 'GovRecruit AI Commission',
        'advertisement_number' => 'TEST/01/2026',
        'total_vacancies' => 999,
        'qualification_level' => 'B.Tech / MCA',
        'state_code' => 'ALL',
        'summary' => 'Test summary for CRUD validation',
        'official_apply_url' => 'https://gov.in/apply',
        'primary_notification_url' => 'https://gov.in/notice.pdf',
        'pay_scale' => 'Level 10 (₹56,100)',
        'age_limit' => '21 to 35 Years',
        'fee_details' => '₹100',
        'start_date' => '2026-09-01',
        'last_date' => '2026-10-01'
    ];
    ob_start();
    $controller->createJob();
    $cRes = json_decode(ob_get_clean(), true);
    $jobId = $cRes['id'] ?? 0;
    
    // 1.2 READ / GET
    $_GET['id'] = $jobId;
    ob_start();
    $controller->getJob();
    $gRes = json_decode(ob_get_clean(), true);
    
    // 1.3 UPDATE
    $_POST = [
        'id' => $jobId,
        'title' => 'UPDATED CRUD Specialist Officer 2026',
        'organization_name' => 'GovRecruit AI Commission',
        'advertisement_number' => 'TEST/01/2026/REV',
        'total_vacancies' => 1200,
        'qualification_level' => 'M.Tech / Ph.D',
        'state_code' => 'DL',
        'status' => 'Exam_Phase',
        'summary' => 'Updated test summary',
        'official_apply_url' => 'https://gov.in/apply-updated',
        'primary_notification_url' => 'https://gov.in/notice-updated.pdf',
        'pay_scale' => 'Level 11 (₹67,700)',
        'age_limit' => '21 to 38 Years',
        'fee_details' => '₹0',
        'start_date' => '2026-09-05',
        'last_date' => '2026-10-05'
    ];
    ob_start();
    $controller->updateJob();
    $uRes = json_decode(ob_get_clean(), true);
    
    // Verify Update in DB
    $chk = $db->query("SELECT title, total_vacancies, status FROM recruitments WHERE id = {$jobId}")->fetch();
    
    // 1.4 DELETE
    $_POST = ['id' => $jobId];
    ob_start();
    $controller->deleteJob();
    $dRes = json_decode(ob_get_clean(), true);
    
    $delChk = $db->query("SELECT COUNT(*) FROM recruitments WHERE id = {$jobId}")->fetchColumn();
    
    $report['recruitments'] = [
        'created' => ($cRes['success'] ?? false) && $jobId > 0,
        'read' => ($gRes['success'] ?? false) && ($gRes['data']['title'] === 'CRUD Test Specialist Officer 2026'),
        'updated' => ($uRes['success'] ?? false) && ($chk['title'] === 'UPDATED CRUD Specialist Officer 2026') && ($chk['total_vacancies'] == 1200),
        'deleted' => ($dRes['success'] ?? false) && ($delChk == 0)
    ];
} catch (Exception $e) {
    $report['recruitments_error'] = $e->getMessage();
}

// ==========================================
// 2. EXAM HUBS CRUD TEST
// ==========================================
try {
    // 2.1 CREATE
    $_POST = [
        'name' => 'National Testing Intelligence Exam 2026',
        'short_name' => 'NTIE 2026',
        'conducting_body' => 'National Testing Agency',
        'category' => 'Engineering',
        'overview' => 'Test overview for exam',
        'eligibility_summary' => 'Engineering Degree',
        'age_limit_summary' => '18-30 Years',
        'official_website' => 'https://nta.ac.in'
    ];
    ob_start();
    $controller->createExam();
    $exC = json_decode(ob_get_clean(), true);
    $examId = $exC['id'] ?? 0;
    
    // 2.2 READ / GET
    $_GET['id'] = $examId;
    ob_start();
    $controller->getExam();
    $exG = json_decode(ob_get_clean(), true);
    
    // 2.3 UPDATE
    $_POST = [
        'id' => $examId,
        'name' => 'UPDATED National Intelligence Exam 2026',
        'short_name' => 'UNIE 2026',
        'conducting_body' => 'National Testing Agency (Updated)',
        'category' => 'Civil Services',
        'frequency' => 'Bi-Annual',
        'overview' => 'Updated exam overview',
        'eligibility_summary' => 'Master Degree',
        'age_limit_summary' => '21-35 Years',
        'official_website' => 'https://nta.ac.in/updated',
        'is_active' => 1
    ];
    ob_start();
    $controller->updateExam();
    $exU = json_decode(ob_get_clean(), true);
    
    $exChk = $db->query("SELECT name, short_name, category FROM exams WHERE id = {$examId}")->fetch();
    
    // 2.4 DELETE
    $_POST = ['id' => $examId];
    ob_start();
    $controller->deleteExam();
    $exD = json_decode(ob_get_clean(), true);
    
    $exDelChk = $db->query("SELECT COUNT(*) FROM exams WHERE id = {$examId}")->fetchColumn();
    
    $report['exams'] = [
        'created' => ($exC['success'] ?? false) && $examId > 0,
        'read' => ($exG['success'] ?? false) && ($exG['data']['short_name'] === 'NTIE 2026'),
        'updated' => ($exU['success'] ?? false) && ($exChk['short_name'] === 'UNIE 2026'),
        'deleted' => ($exD['success'] ?? false) && ($exDelChk == 0)
    ];
} catch (Exception $e) {
    $report['exams_error'] = $e->getMessage();
}

// ==========================================
// 3. ARTICLES / GUIDES CRUD TEST
// ==========================================
try {
    // 3.1 CREATE
    $_POST = [
        'title' => 'Complete Strategy for NTIE 2026 Exam',
        'article_type' => 'Preparation_Strategy',
        'excerpt' => 'Test excerpt for NTIE strategy',
        'content' => '# Complete Strategy Guide\\n\\nComprehensive preparation steps...',
        'reading_time_minutes' => 7,
        'quality_score' => 98
    ];
    ob_start();
    $controller->createArticle();
    $artC = json_decode(ob_get_clean(), true);
    $artId = $artC['id'] ?? 0;
    
    // 3.2 READ / GET
    $_GET['id'] = $artId;
    ob_start();
    $controller->getArticle();
    $artG = json_decode(ob_get_clean(), true);
    
    // 3.3 UPDATE
    $_POST = [
        'id' => $artId,
        'title' => 'UPDATED Strategy & Cutoff Blueprint for NTIE 2026',
        'article_type' => 'Cutoff_Analysis',
        'excerpt' => 'Updated excerpt',
        'content' => '# Updated Content with Cutoffs...',
        'reading_time_minutes' => 10,
        'quality_score' => 99,
        'status' => 'Published'
    ];
    ob_start();
    $controller->updateArticle();
    $artU = json_decode(ob_get_clean(), true);
    
    $artChk = $db->query("SELECT title, reading_time_minutes, article_type FROM articles WHERE id = {$artId}")->fetch();
    
    // 3.4 DELETE
    $_POST = ['id' => $artId];
    ob_start();
    $controller->deleteArticle();
    $artD = json_decode(ob_get_clean(), true);
    
    $artDelChk = $db->query("SELECT COUNT(*) FROM articles WHERE id = {$artId}")->fetchColumn();
    
    $report['articles'] = [
        'created' => ($artC['success'] ?? false) && $artId > 0,
        'read' => ($artG['success'] ?? false) && ($artG['data']['title'] === 'Complete Strategy for NTIE 2026 Exam'),
        'updated' => ($artU['success'] ?? false) && ($artChk['reading_time_minutes'] == 10),
        'deleted' => ($artD['success'] ?? false) && ($artDelChk == 0)
    ];
} catch (Exception $e) {
    $report['articles_error'] = $e->getMessage();
}

echo json_encode($report, JSON_PRETTY_PRINT);
"""

with open(r"c:\hk\hamarijobs\scratch\test_runner.php", "w", encoding="utf-8") as f:
    f.write(test_runner_php)

res = subprocess.run(["php", r"c:\hk\hamarijobs\scratch\test_runner.php"], capture_output=True, text=True, cwd=r"c:\hk\hamarijobs")
print(res.stdout)
if res.stderr:
    print("STDERR:", res.stderr)
