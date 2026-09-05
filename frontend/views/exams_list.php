<?php
require_once __DIR__ . '/../../backend/app/Database.php';
use App\Database;

$db = Database::getConnection();

$search = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');

$conditions = ["is_active = 1"];
$params = [];

if (!empty($search)) {
    $conditions[] = "(name LIKE ? OR short_name LIKE ? OR conducting_body LIKE ? OR overview LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if (!empty($category)) {
    $conditions[] = "category = ?";
    $params[] = $category;
}

$whereClause = implode(' AND ', $conditions);

$stmt = $db->prepare("SELECT * FROM exams WHERE {$whereClause} ORDER BY category ASC, name ASC");
$stmt->execute($params);
$exams = $stmt->fetchAll();

$pageTitle = "Government Exam Intelligence Hubs 2026 — Pattern, Syllabus, Cutoffs & Schemes";
$pageDesc = "Explore complete examination schemes, multi-phase patterns, topic weightages, and previous year cutoff trends for UPSC, SSC, RRB, IBPS, and State PSCs.";
require_once __DIR__ . '/partials/header.php';
?>

<div class="container" style="padding: 3rem 0 5rem;">
  
  <div style="margin-bottom: 2.5rem;">
    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.4rem;">
      <span class="badge-org">EXAM HUBS</span>
      <span style="font-size: 0.825rem; color: var(--text-muted); font-weight: 600;">Autonomous Schemes & Cutoff Records</span>
    </div>
    <h1 style="font-family: var(--font-heading); font-size: 2.35rem; font-weight: 800; color: var(--text-primary); letter-spacing: -0.02em;">
      Government Examination Intelligence Hubs
    </h1>
    <p style="color: var(--text-secondary); font-size: 1rem; margin-top: 0.25rem;">
      Access official test patterns, negative marking rules, syllabus weightage distributions, and multi-year cutoff records.
    </p>
  </div>

  <!-- Search & Filter Bar -->
  <div class="filter-panel">
    <form method="GET" action="/exams" class="filter-grid-3">
      <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search by Exam Name, Short Code, Commission..." class="form-control">
      
      <select name="category" class="form-control">
        <option value="">All Categories</option>
        <option value="Civil Services" <?= $category === 'Civil Services' ? 'selected' : '' ?>>Civil Services</option>
        <option value="Staff Selection" <?= $category === 'Staff Selection' ? 'selected' : '' ?>>Staff Selection</option>
        <option value="Railways" <?= $category === 'Railways' ? 'selected' : '' ?>>Railways</option>
        <option value="Banking" <?= $category === 'Banking' ? 'selected' : '' ?>>Banking</option>
        <option value="Defense" <?= $category === 'Defense' ? 'selected' : '' ?>>Defense & Air Force</option>
        <option value="State PSC" <?= $category === 'State PSC' ? 'selected' : '' ?>>State PSC</option>
      </select>

      <div style="display: flex; gap: 0.5rem;">
        <button type="submit" class="btn btn-primary" style="flex: 1;"><?= app_icon('search', '', 14) ?> Filter</button>
        <?php if (!empty($search) || !empty($category)): ?>
          <a href="/exams" class="btn btn-glass" title="Reset Filters">✕</a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- Exam Hubs Grid -->
  <?php if (empty($exams)): ?>
    <div class="content-box" style="text-align: center; padding: 4rem 1.5rem;">
      <div style="font-size: 2.5rem; margin-bottom: 0.5rem;"></div>
      <h3 style="font-family: var(--font-heading); font-size: 1.35rem; font-weight: 700; color: var(--text-primary);">No matching Exam Hubs found</h3>
      <p style="color: var(--text-secondary); margin-top: 0.25rem;">Try searching for "CGL", "CSE", "NTPC", or "PO".</p>
      <a href="/exams" class="btn btn-primary" style="margin-top: 1.5rem;">Reset Filter</a>
    </div>
  <?php else: ?>
    <div class="exam-grid">
      <?php foreach ($exams as $exam): ?>
        <div class="exam-card">
          <div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
              <span class="badge-org"><?= htmlspecialchars($exam['category']) ?></span>
              <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;"><?= htmlspecialchars($exam['frequency']) ?></span>
            </div>

            <h3 style="font-family: var(--font-heading); font-size: 1.3rem; font-weight: 800; margin-bottom: 0.4rem; color: var(--text-primary);">
              <?= htmlspecialchars($exam['name']) ?>
            </h3>
            
            <div style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.85rem;">
              Conducting Body: <strong><?= htmlspecialchars($exam['conducting_body']) ?></strong>
            </div>

            <p style="font-size: 0.9rem; color: var(--text-secondary); line-height: 1.6; margin-bottom: 1.5rem;">
              <?= htmlspecialchars(substr($exam['overview'], 0, 180)) ?>...
            </p>
          </div>

          <div style="border-top: 1px solid var(--border-subtle); padding-top: 1rem; display: flex; gap: 0.5rem;">
            <a href="/exams/<?= htmlspecialchars($exam['slug']) ?>" class="btn btn-primary btn-sm" style="flex: 1; text-align: center;">
              View Pattern, Syllabus & Cutoffs &rarr;
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
