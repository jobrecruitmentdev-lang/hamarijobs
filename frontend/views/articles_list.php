<?php
require_once __DIR__ . '/../../backend/app/Database.php';
use App\Database;

$db = Database::getConnection();

$search = trim($_GET['q'] ?? '');
$type = trim($_GET['type'] ?? '');

$conditions = ["status = 'Published'"];
$params = [];

if (!empty($search)) {
    $conditions[] = "(title LIKE ? OR excerpt LIKE ? OR content LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if (!empty($type)) {
    $conditions[] = "article_type = ?";
    $params[] = $type;
}

$whereClause = implode(' AND ', $conditions);

$stmt = $db->prepare("SELECT * FROM articles WHERE {$whereClause} ORDER BY published_at DESC LIMIT 50");
$stmt->execute($params);
$articles = $stmt->fetchAll();

$pageTitle = "Government Exam Preparation Guides & Editorial Analysis 2026";
$pageDesc = "In-depth editorial breakdowns of official gazette notifications, section-wise syllabus weightages, eligibility rules, and historical cutoff trends.";
require_once __DIR__ . '/partials/header.php';
?>

<div class="container" style="padding: 3rem 0 5rem;">
  
  <div style="margin-bottom: 2.5rem;">
    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.4rem;">
      <span class="badge-org">PREPARATION GUIDES</span>
      <span style="font-size: 0.825rem; color: var(--text-muted); font-weight: 600;">Verified Ground Truth Editorial Reports</span>
    </div>
    <h1 style="font-family: var(--font-heading); font-size: 2.35rem; font-weight: 800; color: var(--text-primary); letter-spacing: -0.02em;">
      Government Recruitment Preparation Guides
    </h1>
    <p style="color: var(--text-secondary); font-size: 1rem; margin-top: 0.25rem;">
      Comprehensive notifications breakdowns, topic weightage distributions, previous year analysis, and study roadmaps.
    </p>
  </div>

  <!-- Search & Filter Bar -->
  <div class="filter-panel">
    <form method="GET" action="/articles" class="filter-grid" style="grid-template-columns: 2fr 1fr auto;">
      <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search guides by keyword, exam, topic..." class="form-control">
      
      <select name="type" class="form-control">
        <option value="">All Guide Types</option>
        <option value="Notification_Guide" <?= $type === 'Notification_Guide' ? 'selected' : '' ?>>Notification Breakdown</option>
        <option value="Eligibility_Guide" <?= $type === 'Eligibility_Guide' ? 'selected' : '' ?>>Eligibility & Age Limit</option>
        <option value="Exam_Pattern" <?= $type === 'Exam_Pattern' ? 'selected' : '' ?>>Exam Pattern & Marking</option>
        <option value="Syllabus_Breakdown" <?= $type === 'Syllabus_Breakdown' ? 'selected' : '' ?>>Syllabus Breakdown</option>
        <option value="Cutoff_Analysis" <?= $type === 'Cutoff_Analysis' ? 'selected' : '' ?>>Cutoff Analysis</option>
      </select>

      <div style="display: flex; gap: 0.5rem;">
        <button type="submit" class="btn btn-primary">Filter Guides</button>
        <?php if (!empty($search) || !empty($type)): ?>
          <a href="/articles" class="btn btn-glass">Reset</a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- Articles Grid -->
  <?php if (empty($articles)): ?>
    <div class="content-box" style="text-align: center; padding: 4rem 1.5rem;">
      <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🔍</div>
      <h3 style="font-family: var(--font-heading); font-size: 1.35rem; font-weight: 700; color: var(--text-primary);">No matching preparation guides found</h3>
      <p style="color: var(--text-secondary); margin-top: 0.25rem;">Try choosing "All Guide Types" or searching with broader keywords.</p>
      <a href="/articles" class="btn btn-primary" style="margin-top: 1.5rem;">Reset Filter</a>
    </div>
  <?php else: ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 1.75rem;">
      <?php foreach ($articles as $art): ?>
        <div class="job-card">
          <div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
              <span class="badge-org" style="font-size: 0.7rem;">
                <?= htmlspecialchars(str_replace('_', ' ', $art['article_type'])) ?>
              </span>
              <span style="font-size: 0.75rem; color: var(--text-muted);">
                ⏱ <?= $art['reading_time_minutes'] ?> min read
              </span>
            </div>

            <h3 style="font-family: var(--font-heading); font-size: 1.25rem; font-weight: 700; line-height: 1.35; margin-bottom: 0.6rem; color: var(--text-primary);">
              <a href="/articles/<?= htmlspecialchars($art['slug']) ?>">
                <?= htmlspecialchars($art['title']) ?>
              </a>
            </h3>

            <p style="font-size: 0.9rem; color: var(--text-secondary); line-height: 1.6; margin-bottom: 1.25rem;">
              <?= htmlspecialchars($art['excerpt']) ?>
            </p>
          </div>

          <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-subtle); padding-top: 0.85rem;">
            <span style="font-size: 0.775rem; color: var(--emerald); font-weight: 700;">
              ✓ Truth Score: <?= $art['quality_score'] ?>/100
            </span>
            <a href="/articles/<?= htmlspecialchars($art['slug']) ?>" class="btn btn-outline btn-sm">
              Read Complete Guide &rarr;
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
