import subprocess
import json

test_runner_php = """<?php
require_once __DIR__ . '/../backend/app/Database.php';
require_once __DIR__ . '/../backend/app/Services/JobArticleGenerator.php';

use App\Database;
use App\Services\JobArticleGenerator;

$db = Database::getConnection();
$jobs = $db->query("SELECT id, title, slug, organization_name FROM recruitments WHERE status = 'Active'")->fetchAll();

$results = [];

foreach ($jobs as $j) {
    $_GET['slug'] = $j['slug'];
    $_SERVER['REQUEST_URI'] = '/jobs/' . $j['slug'];
    $_SERVER['REQUEST_METHOD'] = 'GET';
    
    ob_start();
    require __DIR__ . '/../frontend/views/job_detail.php';
    $html = ob_get_clean();
    
    // Extract text from job-article-wrapper to calculate word count
    preg_match('/<div class="job-article-wrapper">(.*?)<\\/div>\\s*<\\/div>\\s*<\\!-- Right Column/s', $html, $matches);
    $articleHtml = $matches[1] ?? $html;
    $plainText = strip_tags($articleHtml);
    $words = str_word_count($plainText);
    
    // Count FAQs in this article
    $faqCount = substr_count($articleHtml, 'class="job-article-faq-item"');
    
    // Count Backlinks to https://jobrecruitment.in/
    $backlinkCount = substr_count($html, 'https://jobrecruitment.in/');
    
    $results[] = [
        'job_id' => $j['id'],
        'title' => $j['title'],
        'org' => $j['organization_name'],
        'word_count' => $words,
        'faq_count' => $faqCount,
        'backlink_count' => $backlinkCount,
        'has_highlights' => strpos($articleHtml, 'Key Recruitment Highlights') !== false,
        'has_perks' => strpos($articleHtml, 'Cadre Breakdown, Posts Matrix') !== false,
        'has_normalization' => strpos($articleHtml, 'Multi-Shift Normalization') !== false,
        'has_cutoffs' => strpos($articleHtml, 'Historical Cutoff Trends') !== false,
        'has_roadmap' => strpos($articleHtml, '90-Day Structured Self-Study Roadmap') !== false,
        'has_backlink_card' => strpos($articleHtml, 'backlink-partner-card') !== false,
        'meets_word_count' => ($words >= 2000 && $words <= 2600),
        'success' => ($words >= 2000) && ($faqCount >= 4 && $faqCount <= 6) && ($backlinkCount >= 2)
    ];
}

echo json_encode($results, JSON_PRETTY_PRINT);
"""

with open(r"c:\hk\hamarijobs\scratch\run_article_test_full.php", "w", encoding="utf-8") as f:
    f.write(test_runner_php)

res = subprocess.run(["php", r"c:\hk\hamarijobs\scratch\run_article_test_full.php"], capture_output=True, text=True, cwd=r"c:\hk\hamarijobs")
print(res.stdout)
if res.stderr:
    print("STDERR:", res.stderr)
