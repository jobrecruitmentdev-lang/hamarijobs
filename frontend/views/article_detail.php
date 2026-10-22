<?php
require_once __DIR__ . '/../../backend/app/Database.php';
use App\Database;

$slug = $_GET['slug'] ?? '';
$db = Database::getConnection();

$stmt = $db->prepare("SELECT * FROM articles WHERE slug = ? AND status = 'Published' LIMIT 1");
$stmt->execute([$slug]);
$article = $stmt->fetch();

if (!$article) {
    http_response_code(404);
    $pageTitle = "Guide Not Found — Government Recruitment Intelligence";
    require_once __DIR__ . '/partials/header.php';
    echo "<div class='container' style='padding: 6rem 0; text-align: center;'><h2>404 — Article Not Found</h2><p style='color: var(--text-secondary); margin: 1rem 0 2rem;'>The requested editorial guide does not exist.</p><a href='/' class='btn btn-primary'>Back to Home</a></div>";
    require_once __DIR__ . '/partials/footer.php';
    exit;
}

// Increment view count
$db->prepare("UPDATE articles SET view_count = view_count + 1 WHERE id = ?")->execute([$article['id']]);

// Fetch linked recruitment
$rec = null;
$siblingArticles = [];
if (!empty($article['recruitment_id'])) {
    $recStmt = $db->prepare("SELECT id, title, slug, organization_name, total_vacancies, official_apply_url FROM recruitments WHERE id = ? LIMIT 1");
    $recStmt->execute([$article['recruitment_id']]);
    $recRow = $recStmt->fetch();
    $rec = is_array($recRow) ? $recRow : null;

    // Fetch sibling guides in this cluster (Silo Interlinking)
    $sibStmt = $db->prepare("SELECT id, title, slug, article_type FROM articles WHERE recruitment_id = ? AND id != ? AND status = 'Published' ORDER BY id ASC");
    $sibStmt->execute([$article['recruitment_id'], $article['id']]);
    $siblingArticles = $sibStmt->fetchAll();
}

require_once __DIR__ . '/../../backend/app/Services/SeoEngine.php';
require_once __DIR__ . '/../../backend/app/Services/MarkdownRenderer.php';
use App\Services\MarkdownRenderer;

$seo = \App\Services\SeoEngine::getArticleDetailSeo($article, $rec);

require_once __DIR__ . '/partials/header.php';
?>

<div class="container" style="padding: 2.5rem 0 5rem; max-width: 960px;">
  
  <!-- Breadcrumb -->
  <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.5rem;">
    <a href="/" style="color: var(--text-secondary); text-decoration: none;">Home</a> &nbsp;&rsaquo;&nbsp; 
    <a href="/articles" style="color: var(--text-secondary); text-decoration: none;">Preparation Guides</a> &nbsp;&rsaquo;&nbsp; 
    <span style="color: var(--primary-red); font-weight: 600;"><?= htmlspecialchars(substr($article['title'], 0, 45)) ?>...</span>
  </div>

  <article class="content-box" style="padding: 3rem 2.5rem;">
    
    <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1.25rem;">
      <span class="badge-org">
        <?= htmlspecialchars(str_replace('_', ' ', $article['article_type'])) ?>
      </span>
      <div style="font-size: 0.825rem; color: var(--text-muted);">
        ⏱ <?= $article['reading_time_minutes'] ?> min read • Published: <?= date('d M Y', strtotime($article['published_at'])) ?>
      </div>
    </div>

    <!-- AEO Direct-Answer Quick Fact Sheet (Target for Google AI Overviews & Perplexity) -->
    <?php if (!empty($seo['tldr_box'])): ?>
      <div class="aeo-answer-card" style="background: var(--bg-surface-elevated, #f8fafc); border: 1px solid var(--border-subtle, #e2e8f0); border-left: 4px solid var(--primary-red); border-radius: 8px; padding: 1.25rem 1.5rem; margin-bottom: 2rem;">
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.4rem;">
          <span style="font-size: 0.75rem; font-weight: 800; letter-spacing: 0.05em; text-transform: uppercase; color: var(--primary-red); background: #fee2e2; padding: 0.2rem 0.6rem; border-radius: 4px;">
            ⚡ Direct Fact Sheet (AEO Summary)
          </span>
        </div>
        <p style="font-size: 1.025rem; line-height: 1.6; color: var(--text-primary); margin: 0; font-weight: 500;">
          <?= htmlspecialchars($seo['tldr_box']) ?>
        </p>
      </div>
    <?php endif; ?>

    <!-- Verified Truth Callout -->
    <div style="background: var(--bg-red-subtle); border-left: 4px solid var(--primary-red); border-radius: var(--radius-xs); padding: 1rem 1.25rem; margin-bottom: 2rem;">
      <div style="font-weight: 700; color: var(--primary-red-dark); font-size: 0.9rem; margin-bottom: 0.25rem;">
        ✓ Verified Ground Truth Protocol (Quality Score: <?= $article['quality_score'] ?>/100)
      </div>
      <p style="font-size: 0.85rem; color: var(--text-secondary); line-height: 1.5; margin: 0;">
        All timeline dates, vacancies, eligibility criteria, and examination structures in this intelligence guide have been cross-verified directly against official Government of India gazettes and archived on the <a href="https://jobrecruitment.in/" target="_blank" rel="noopener" style="color: var(--primary-red); font-weight: 700; text-decoration: underline;"><?= htmlspecialchars($article['title']) ?></a> portal.
      </p>
    </div>

    <!-- Rich Semantic Markdown Rendered Content (with HTML Tables, Heading IDs & Lists) -->
    <div class="article-content" style="color: var(--text-primary); line-height: 1.85; font-size: 1.05rem;">
      <?= MarkdownRenderer::render($article['content']) ?>
    </div>

    <!-- Editorial Resource & Knowledge Repository Note -->
    <div style="margin-top: 2.5rem; padding: 1.25rem 1.5rem; background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); border-left: 4px solid var(--primary-red); border-radius: var(--radius-sm); font-size: 0.925rem; color: var(--text-secondary); line-height: 1.65;">
      <strong style="color: var(--text-primary);">📖 Preparation & Reference Desk:</strong> For complete syllabus weightages, sectional mock test blueprints, and previous year cutoff trends across national recruitment cycles, consult the <a href="https://jobrecruitment.in/" target="_blank" rel="noopener" style="color: var(--primary-red); font-weight: 700; text-decoration: underline;">Government Job Recruitment</a> analytical knowledge portal.
    </div>

    <!-- Silo Internal Linking Mesh (Other Guides for this Recruitment) -->
    <?php if (!empty($siblingArticles)): ?>
      <div style="margin-top: 2.5rem; background: var(--bg-subtle, #f8fafc); border: 1px solid var(--border-subtle, #e2e8f0); border-radius: 8px; padding: 1.5rem;">
        <h4 style="font-family: var(--font-heading); font-size: 1.15rem; font-weight: 700; margin-bottom: 1rem; color: var(--text-primary);">
          📚 Complete Examination Intelligence Cluster
        </h4>
        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
          <?php foreach ($siblingArticles as $sib): ?>
            <a href="/articles/<?= htmlspecialchars($sib['slug']) ?>" style="display: flex; align-items: center; justify-content: space-between; text-decoration: none; padding: 0.85rem 1rem; background: #ffffff; border: 1px solid var(--border-subtle, #e2e8f0); border-radius: 6px; transition: border-color 0.2s;">
              <span style="font-weight: 600; color: var(--primary-red); font-size: 0.95rem;">
                &rsaquo; <?= htmlspecialchars($sib['title']) ?>
              </span>
              <span style="font-size: 0.75rem; background: var(--bg-subtle); padding: 0.2rem 0.5rem; border-radius: 4px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">
                <?= htmlspecialchars(str_replace('_', ' ', $sib['article_type'])) ?>
              </span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- Related Recruitment CTA if attached -->
    <?php if ($rec): ?>
      <div style="background: var(--bg-subtle); border: 1px solid var(--border-red); border-radius: var(--radius-md); padding: 1.5rem; margin-top: 2rem; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem;">
        <div>
          <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Linked Official Recruitment Notice</div>
          <h4 style="font-family: var(--font-heading); font-size: 1.2rem; font-weight: 700; margin-top: 0.2rem; color: var(--text-primary);">
            <?= htmlspecialchars($rec['organization_name']) ?> <?= htmlspecialchars($rec['title']) ?>
          </h4>
        </div>
        <a href="/jobs/<?= htmlspecialchars($rec['slug']) ?>" class="btn btn-primary">
          View Complete Job Dossier &rarr;
        </a>
      </div>
    <?php endif; ?>

  </article>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
