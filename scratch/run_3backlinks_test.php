<?php
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
    preg_match('/<div class="job-article-wrapper">(.*?)<\/div>\s*<\/div>\s*<\!--/s', $html, $matches);
    $articleHtml = $matches[1] ?? $html;
    $plainText = strip_tags($articleHtml);
    $words = str_word_count($plainText);
    
    // Count FAQs in this article
    $faqCount = substr_count($articleHtml, 'class="job-article-faq-item"');
    
    // Count exact occurrences of https://jobrecruitment.in/
    $backlinkCount = substr_count($html, 'https://jobrecruitment.in/');
    
    // Check if the Job Title is linked
    $titleLinked = strpos($articleHtml, '>' . htmlspecialchars($j['title']) . '</a>') !== false;
    
    $results[] = [
        'job_id' => $j['id'],
        'title' => $j['title'],
        'word_count' => $words,
        'faq_count' => $faqCount,
        'backlink_count' => $backlinkCount,
        'is_job_title_linked' => $titleLinked,
        'success' => ($words >= 2100) && ($faqCount >= 4 && $faqCount <= 6) && ($backlinkCount === 3) && $titleLinked
    ];
}

echo json_encode($results, JSON_PRETTY_PRINT);
