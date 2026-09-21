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

require_once __DIR__ . '/../../backend/app/Services/SeoEngine.php';
$seo = \App\Services\SeoEngine::getJobDetailSeo($rec, $factsMap, $events);

require_once __DIR__ . '/partials/header.php';
?>

<div class="container" style="padding: 2rem 0 5rem; max-width: 1040px; margin: 0 auto;">
  
  <!-- Clean Editorial Breadcrumb -->
  <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
    <a href="/" style="color: var(--text-secondary); text-decoration: none;">Home</a> 
    <span>&rsaquo;</span>
    <a href="/government-jobs" style="color: var(--text-secondary); text-decoration: none;">Government Jobs</a> 
    <span>&rsaquo;</span>
    <span style="color: var(--primary-red); font-weight: 600;"><?= htmlspecialchars($rec['title']) ?></span>
  </div>

  <!-- AEO Direct-Answer TL;DR Box for Google AI Overviews & Voice Search -->
  <?php if (!empty($seo['tldr_box'])): ?>
    <div class="aeo-tldr-card" style="background: #f8fafc; border: 1px solid #e2e8f0; border-left: 4px solid var(--primary-red); border-radius: 8px; padding: 1.25rem 1.5rem; margin-bottom: 2rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
      <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
        <span style="font-size: 0.75rem; font-weight: 800; letter-spacing: 0.05em; text-transform: uppercase; color: var(--primary-red); background: #fee2e2; padding: 0.2rem 0.6rem; border-radius: 4px;">
          ⚡ AI Fact Snapshot (Verified Gazette)
        </span>
      </div>
      <p style="font-size: 1.025rem; line-height: 1.6; color: #1e293b; margin: 0; font-weight: 500;">
        <?= $seo['tldr_box'] ?>
      </p>
    </div>
  <?php endif; ?>

  <!-- COMPREHENSIVE EDITORIAL BLOG ARTICLE WITH TARGETED BACKLINKS -->
  <?= JobArticleGenerator::generateArticle($rec, $factsMap, $events, $primaryArticle) ?>

  <?php
    // Query Related Recruitments
    $relatedStmt = $db->prepare("SELECT id, title, slug, organization_name, total_vacancies, qualification_level FROM recruitments WHERE id != ? AND (organization_name = ? OR qualification_level = ?) AND status != 'Archived' ORDER BY updated_at DESC LIMIT 3");
    $relatedStmt->execute([$recId, $rec['organization_name'], $rec['qualification_level']]);
    $relatedJobs = $relatedStmt->fetchAll();

    // Query Matching Exam Hub
    $examMatchStmt = $db->prepare("SELECT id, name, short_name, slug FROM exams WHERE is_active = 1 AND (? LIKE CONCAT('%', short_name, '%') OR ? LIKE CONCAT('%', short_name, '%')) LIMIT 1");
    $examMatchStmt->execute([$rec['title'], $rec['organization_name']]);
    $examMatch = $examMatchStmt->fetch() ?: null;

    // Query Matching Commission
    $commMatchStmt = $db->prepare("SELECT id, name, short_name, slug FROM commissions WHERE is_active = 1 AND (? LIKE CONCAT('%', short_name, '%') OR ? LIKE CONCAT('%', short_name, '%')) LIMIT 1");
    $commMatchStmt->execute([$rec['title'], $rec['organization_name']]);
    $commMatch = $commMatchStmt->fetch() ?: null;
  ?>

  <!-- Dedicated Cross-Linking & Crawl Hub Footer Box -->
  <div style="margin-top: 3.5rem; background: #ffffff; border: 1px solid var(--border-subtle); border-radius: 12px; padding: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.03);">
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; padding-bottom: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
      <div>
        <h3 style="font-size: 1.25rem; font-weight: 800; color: #0f172a; margin: 0;">🔗 Official Portals, Related Exams & Gazette Archives</h3>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0.25rem 0 0;">Connected intelligence resources for <?= htmlspecialchars($rec['title']) ?></p>
      </div>
      <a href="/sitemap" style="color: var(--primary-red); font-size: 0.85rem; font-weight: 700; text-decoration: none;">View Master Sitemap &rarr;</a>
    </div>

    <!-- Micro Navigation Matrix -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
      <?php if ($commMatch): ?>
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem;">
          <span style="font-size: 0.75rem; font-weight: 800; color: var(--primary-red); text-transform: uppercase;">Recruiting Body</span>
          <div style="margin-top: 0.35rem; font-weight: 700; font-size: 0.95rem;">
            <a href="/commissions/<?= htmlspecialchars($commMatch['slug']) ?>" style="color: #0284c7; text-decoration: none;">
              🏛️ <?= htmlspecialchars($commMatch['name']) ?>
            </a>
          </div>
        </div>
      <?php endif; ?>

      <?php if ($examMatch): ?>
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem;">
          <span style="font-size: 0.75rem; font-weight: 800; color: var(--primary-red); text-transform: uppercase;">Exam Intelligence</span>
          <div style="margin-top: 0.35rem; font-weight: 700; font-size: 0.95rem;">
            <a href="/exams/<?= htmlspecialchars($examMatch['slug']) ?>" style="color: #0284c7; text-decoration: none;">
              🎯 <?= htmlspecialchars($examMatch['short_name']) ?> Hub &amp; Syllabus
            </a>
          </div>
        </div>
      <?php endif; ?>

      <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem;">
        <span style="font-size: 0.75rem; font-weight: 800; color: var(--primary-red); text-transform: uppercase;">Hall Tickets</span>
        <div style="margin-top: 0.35rem; font-weight: 700; font-size: 0.95rem;">
          <a href="/admit-cards" style="color: #0284c7; text-decoration: none;">
            🎫 Download Admit Cards
          </a>
        </div>
      </div>

      <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem;">
        <span style="font-size: 0.75rem; font-weight: 800; color: var(--primary-red); text-transform: uppercase;">Merit Lists</span>
        <div style="margin-top: 0.35rem; font-weight: 700; font-size: 0.95rem;">
          <a href="/results" style="color: #0284c7; text-decoration: none;">
            🏆 Latest Exam Results
          </a>
        </div>
      </div>
    </div>

    <?php if (!empty($relatedJobs)): ?>
      <div>
        <h4 style="font-size: 0.95rem; font-weight: 800; color: #334155; margin-bottom: 0.75rem; text-transform: uppercase; letter-spacing: 0.03em;">
          Related Official Openings
        </h4>
        <div style="display: flex; flex-direction: column; gap: 0.65rem;">
          <?php foreach ($relatedJobs as $rj): ?>
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.65rem 0.85rem; background: #fafafa; border-radius: 6px; font-size: 0.9rem;">
              <a href="/jobs/<?= htmlspecialchars($rj['slug']) ?>" style="color: #0f172a; text-decoration: none; font-weight: 600;">
                <?= htmlspecialchars($rj['title']) ?>
              </a>
              <span style="font-size: 0.8rem; color: var(--primary-red); font-weight: 700; white-space: nowrap; margin-left: 1rem;">
                <?= $rj['total_vacancies'] ? number_format($rj['total_vacancies']) . ' Posts' : 'Active' ?> &rarr;
              </span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
