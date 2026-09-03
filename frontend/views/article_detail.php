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
if (!empty($article['recruitment_id'])) {
    $recStmt = $db->prepare("SELECT id, title, slug, organization_name, total_vacancies, official_apply_url FROM recruitments WHERE id = ? LIMIT 1");
    $recStmt->execute([$article['recruitment_id']]);
    $rec = $recStmt->fetch();
}

$pageTitle = "{$article['title']} — Official Recruitment Intelligence Guide";
$pageDesc = htmlspecialchars($article['excerpt'] ?: substr(strip_tags($article['content']), 0, 160));
$canonicalUrl = "https://hamarijobs.com/articles/{$article['slug']}";

require_once __DIR__ . '/partials/header.php';
?>

<div class="container" style="padding: 2.5rem 0 5rem; max-width: 920px;">
  
  <!-- Breadcrumb -->
  <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.5rem;">
    <a href="/" style="color: var(--text-secondary);">Home</a> &nbsp;/&nbsp; 
    <a href="/#articles" style="color: var(--text-secondary);">Preparation Guides</a> &nbsp;/&nbsp; 
    <span style="color: var(--primary-red); font-weight: 600;"><?= htmlspecialchars(substr($article['title'], 0, 40)) ?>...</span>
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

    <!-- Verified Truth Callout -->
    <div style="background: var(--bg-red-subtle); border-left: 4px solid var(--primary-red); border-radius: var(--radius-xs); padding: 1rem 1.25rem; margin-bottom: 2rem;">
      <div style="font-weight: 700; color: var(--primary-red-dark); font-size: 0.9rem; margin-bottom: 0.25rem;">
        ✓ Verified Ground Truth Protocol (Quality Score: <?= $article['quality_score'] ?>/100)
      </div>
      <p style="font-size: 0.85rem; color: var(--text-secondary); line-height: 1.5;">
        All timeline dates, vacancies, eligibility criteria, and examination structures in this intelligence guide have been cross-verified directly against official Government of India gazettes.
      </p>
    </div>

    <!-- Markdown Rendered Content -->
    <div class="article-content" style="color: var(--text-primary); line-height: 1.85; font-size: 1.05rem;">
      <?php 
        $html = htmlspecialchars($article['content']);
        // Headings
        $html = preg_replace('/^# (.*?)$/m', '<h1 style="font-family: var(--font-heading); font-size: 2rem; font-weight: 800; margin: 1.5rem 0 1rem; color: var(--text-primary);">$1</h1>', $html);
        $html = preg_replace('/^## (.*?)$/m', '<h2 style="font-family: var(--font-heading); font-size: 1.5rem; font-weight: 700; margin: 2rem 0 0.75rem; color: var(--primary-red-dark);">$1</h2>', $html);
        $html = preg_replace('/^### (.*?)$/m', '<h3 style="font-family: var(--font-heading); font-size: 1.2rem; font-weight: 600; margin: 1.5rem 0 0.5rem; color: var(--text-primary);">$1</h3>', $html);
        // Bold
        $html = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $html);
        // Blockquotes
        $html = preg_replace('/^> (.*?)$/m', '<blockquote style="border-left: 3px solid var(--primary-red); padding-left: 1rem; margin: 1.25rem 0; color: var(--text-secondary); font-style: italic; background: var(--bg-subtle); padding: 0.75rem 1rem; border-radius: 0 var(--radius-xs) var(--radius-xs) 0;">$1</blockquote>', $html);
        // Links
        $html = preg_replace('/\[(.*?)\]\((.*?)\)/', '<a href="$2" target="_blank" rel="noopener" style="color: var(--primary-red); font-weight: 700; text-decoration: underline;">$1</a>', $html);
        // Line breaks
        $html = nl2br($html);
        echo $html;
      ?>
    </div>

    <!-- Related Recruitment CTA if attached -->
    <?php if ($rec): ?>
      <div style="background: var(--bg-subtle); border: 1px solid var(--border-red); border-radius: var(--radius-md); padding: 1.5rem; margin-top: 3rem; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem;">
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
