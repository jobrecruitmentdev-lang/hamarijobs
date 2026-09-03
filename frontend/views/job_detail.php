<?php
require_once __DIR__ . '/../../backend/app/Database.php';
require_once __DIR__ . '/../../backend/app/Services/JobArticleGenerator.php';

use App\Database;
use App\Services\JobArticleGenerator;

$slug = $_GET['slug'] ?? '';
$db = Database::getConnection();

$stmt = $db->prepare("SELECT * FROM recruitments WHERE slug = ? LIMIT 1");
$stmt->execute([$slug]);
$rec = $stmt->fetch();

if (!$rec) {
    // Fallback match
    $stmt = $db->prepare("SELECT * FROM recruitments WHERE title LIKE ? LIMIT 1");
    $stmt->execute(["%{$slug}%"]);
    $rec = $stmt->fetch();
}

if (!$rec) {
    http_response_code(404);
    $pageTitle = "Recruitment Not Found — Government Recruitment Intelligence";
    require_once __DIR__ . '/partials/header.php';
    echo "<div class='container' style='padding: 6rem 0; text-align: center;'><h2>404 — Recruitment Notice Not Found</h2><p style='color: var(--text-secondary); margin: 1rem 0 2rem;'>The requested government job notification could not be located in our active gazette index.</p><a href='/government-jobs' class='btn btn-primary'>Browse All Active Jobs</a></div>";
    require_once __DIR__ . '/partials/footer.php';
    exit;
}

$recId = $rec['id'];

// Fetch Timeline Events
$eventStmt = $db->prepare("SELECT * FROM recruitment_events WHERE recruitment_id = ? ORDER BY event_date ASC, id ASC");
$eventStmt->execute([$recId]);
$events = $eventStmt->fetchAll();

// Fetch Facts
$factStmt = $db->prepare("SELECT * FROM fact_claims WHERE entity_type = 'Recruitment' AND entity_id = ?");
$factStmt->execute([$recId]);
$facts = $factStmt->fetchAll();
$factsMap = [];
foreach ($facts as $f) {
    $factsMap[$f['field_name']] = $f['claimed_value'];
}

// Fetch Primary Custom Article if published
$primaryArticleStmt = $db->prepare("SELECT * FROM articles WHERE recruitment_id = ? AND status = 'Published' ORDER BY quality_score DESC, id DESC LIMIT 1");
$primaryArticleStmt->execute([$recId]);
$primaryArticle = $primaryArticleStmt->fetch() ?: null;

$pageTitle = "{$rec['organization_name']} {$rec['title']} Recruitment 2026: Complete Notification, Vacancies, Syllabus, Pay Scale & Apply Online";
$pageDesc = "Exhaustive editorial guide and notification breakdown for {$rec['organization_name']} {$rec['title']}. Total Vacancies: " . ($rec['total_vacancies'] ?: 'Various') . ". Verified syllabus, cutoff analysis, age limit, salary & online application guide.";
$canonicalUrl = "https://hamarijobs.com/jobs/{$rec['slug']}";

require_once __DIR__ . '/partials/header.php';
?>

<!-- Structured Data JSON-LD for Google Article & JobPosting -->
<script type="application/ld+json">
{
  "@context": "https://schema.org/",
  "@type": "Article",
  "headline": "<?= htmlspecialchars($rec['organization_name'] . ' ' . $rec['title'] . ' Recruitment ' . ($rec['year'] ?: 2026) . ': Complete Guide') ?>",
  "description": "<?= htmlspecialchars($rec['summary'] ?: $rec['title']) ?>",
  "datePublished": "<?= date('Y-m-d', strtotime($rec['created_at'])) ?>",
  "dateModified": "<?= date('Y-m-d', strtotime($rec['updated_at'] ?: $rec['created_at'])) ?>",
  "author": {
    "@type": "Organization",
    "name": "Government Recruitment Intelligence Bureau"
  },
  "publisher": {
    "@type": "Organization",
    "name": "HamariJobs",
    "logo": {
      "@type": "ImageObject",
      "url": "https://hamarijobs.com/assets/images/logo.png"
    }
  }
}
</script>

<div class="container" style="padding: 2rem 0 5rem; max-width: 1040px; margin: 0 auto;">
  
  <!-- Clean Editorial Breadcrumb -->
  <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
    <a href="/" style="color: var(--text-secondary); text-decoration: none;">Home</a> 
    <span>&rsaquo;</span>
    <a href="/government-jobs" style="color: var(--text-secondary); text-decoration: none;">Government Jobs</a> 
    <span>&rsaquo;</span>
    <span style="color: var(--primary-red); font-weight: 600;"><?= htmlspecialchars($rec['title']) ?></span>
  </div>

  <!-- COMPREHENSIVE EDITORIAL BLOG ARTICLE WITH 2 TARGETED BACKLINKS -->
  <?= JobArticleGenerator::generateArticle($rec, $factsMap, $events, $primaryArticle) ?>

</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
