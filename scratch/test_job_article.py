import subprocess
import json

test_runner_php = """<?php
require_once __DIR__ . '/../backend/app/Database.php';
require_once __DIR__ . '/../backend/app/Services/JobArticleGenerator.php';

use App\Database;
use App\Services\JobArticleGenerator;

$db = Database::getConnection();
$jobs = $db->query("SELECT id, title, slug, organization_name FROM recruitments WHERE status = 'Active' LIMIT 5")->fetchAll();

$results = [];

foreach ($jobs as $j) {
    $_GET['slug'] = $j['slug'];
    $_SERVER['REQUEST_URI'] = '/jobs/' . $j['slug'];
    $_SERVER['REQUEST_METHOD'] = 'GET';
    
    ob_start();
    require __DIR__ . '/../frontend/views/job_detail.php';
    $html = ob_get_clean();
    
    $hasArticleWrapper = strpos($html, 'job-article-wrapper') !== false;
    $hasSyllabus = strpos($html, 'Examination Scheme, Syllabus') !== false;
    $hasHowToApply = strpos($html, 'How to Apply Online') !== false;
    $hasFAQs = strpos($html, 'Frequently Asked Questions') !== false;
    
    // Count occurrences of https://jobrecruitment.in/
    $backlinkCount = substr_count($html, 'https://jobrecruitment.in/');
    
    $results[] = [
        'job_id' => $j['id'],
        'title' => $j['title'],
        'slug' => $j['slug'],
        'html_size_bytes' => strlen($html),
        'has_article_wrapper' => $hasArticleWrapper,
        'has_syllabus' => $hasSyllabus,
        'has_how_to_apply' => $hasHowToApply,
        'has_faqs' => $hasFAQs,
        'backlink_count' => $backlinkCount,
        'success' => $hasArticleWrapper && $hasSyllabus && $hasHowToApply && $hasFAQs && ($backlinkCount >= 2)
    ];
}

echo json_encode($results, JSON_PRETTY_PRINT);
"""

with open(r"c:\hk\hamarijobs\scratch\run_article_test.php", "w", encoding="utf-8") as f:
    f.write(test_runner_php)

res = subprocess.run(["php", r"c:\hk\hamarijobs\scratch\run_article_test.php"], capture_output=True, text=True, cwd=r"c:\hk\hamarijobs")
print(res.stdout)
if res.stderr:
    print("STDERR:", res.stderr)
