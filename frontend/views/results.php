<?php
require_once __DIR__ . '/../../backend/app/Database.php';
use App\Database;

$db = Database::getConnection();

$search = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');

$conditions = ["re.event_type IN ('RESULT_DECLARED', 'CUTOFF_RELEASED', 'FINAL_MERIT_LIST', 'ANSWER_KEY_RELEASED')"];
$params = [];

if (!empty($search)) {
    $conditions[] = "(r.title LIKE ? OR r.organization_name LIKE ? OR re.organization_name LIKE ? OR re.event_title LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if (!empty($category)) {
    $conditions[] = "(r.organization_name LIKE ? OR re.organization_name LIKE ?)";
    $params[] = "%{$category}%";
    $params[] = "%{$category}%";
}

$whereClause = implode(' AND ', $conditions);

$stmt = $db->prepare("
    SELECT re.*, r.title as recruitment_title, r.slug as recruitment_slug, COALESCE(r.organization_name, re.organization_name, 'Government of India') as organization_name, COALESCE(r.official_website_url, re.reference_url) as official_website_url
    FROM recruitment_events re
    LEFT JOIN recruitments r ON re.recruitment_id = r.id
    WHERE {$whereClause}
    ORDER BY re.event_date DESC, re.id DESC
");
$stmt->execute($params);
$events = $stmt->fetchAll();

// Also fetch latest cutoffs
$cutStmt = $db->query("
    SELECT c.*, e.name as exam_name, e.slug as exam_slug, e.conducting_body
    FROM cutoff_records c
    LEFT JOIN exams e ON c.exam_id = e.id
    ORDER BY c.year DESC, c.cutoff_marks DESC
    LIMIT 20
");
$cutoffs = $cutStmt->fetchAll();

$pageTitle = "Government Exam Results & Merit Lists 2026 — Official Scorecards & Cutoffs";
$pageDesc = "Check latest official government recruitment examination results, selection merit lists, scorecards, and cutoff marks for UPSC, SSC, Railways, Banking, and State PSCs.";
require_once __DIR__ . '/partials/header.php';
?>

<div class="container" style="padding: 3rem 0 5rem;">
  
  <div style="margin-bottom: 2.5rem;">
    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.4rem;">
      <span class="badge-org">EXAM RESULTS PORTAL</span>
      <span style="font-size: 0.825rem; color: var(--text-muted); font-weight: 600;">Verified Merit Lists & Official Scorecards</span>
    </div>
    <h1 style="font-family: var(--font-heading); font-size: 2.35rem; font-weight: 800; color: var(--text-primary); letter-spacing: -0.02em;">
      Government Exam Results 2026
    </h1>
    <p style="color: var(--text-secondary); font-size: 1rem; margin-top: 0.25rem;">
      Access declared results, qualified candidates lists, scorecard links, and category-wise cutoff marks.
    </p>
  </div>

  <!-- Filter Bar -->
  <div class="filter-panel">
    <form method="GET" action="/results" class="filter-grid" style="grid-template-columns: 2fr 1fr auto;">
      <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search results by Exam, Commission, Post..." class="form-control">
      
      <select name="category" class="form-control">
        <option value="">All Commissions</option>
        <option value="UPSC" <?= $category === 'UPSC' ? 'selected' : '' ?>>UPSC</option>
        <option value="SSC" <?= $category === 'SSC' ? 'selected' : '' ?>>SSC</option>
        <option value="Railways" <?= $category === 'Railways' ? 'selected' : '' ?>>Railways / RRB</option>
        <option value="Banking" <?= $category === 'Banking' ? 'selected' : '' ?>>Banking (IBPS/SBI)</option>
        <option value="Air Force" <?= $category === 'Air Force' ? 'selected' : '' ?>>Defence Forces</option>
        <option value="GPSC" <?= $category === 'GPSC' ? 'selected' : '' ?>>State PSCs</option>
      </select>

      <div style="display: flex; gap: 0.5rem;">
        <button type="submit" class="btn btn-primary">Filter Results</button>
        <?php if (!empty($search) || !empty($category)): ?>
          <a href="/results" class="btn btn-glass">Reset</a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- Declared Results Section -->
  <div class="content-box" style="margin-bottom: 3rem;">
    <h3 class="content-box-title">
      🏆 Declared Results & Merit Lists
    </h3>

    <div style="overflow-x: auto;">
      <table class="data-table">
        <thead>
          <tr>
            <th>Commission / Exam</th>
            <th>Result Announcement</th>
            <th>Declaration Date</th>
            <th>Status</th>
            <th>Official Link</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($events)): ?>
            <tr>
              <td colspan="5" style="text-align: center; padding: 3rem; color: var(--text-secondary);">
                No declared results match your filter criteria.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($events as $ev): ?>
              <tr>
                <td>
                  <span class="badge-org" style="font-size: 0.65rem; margin-right: 0.35rem;"><?= htmlspecialchars($ev['organization_name']) ?></span>
                  <?php if (!empty($ev['recruitment_slug'])): ?>
                    <a href="/jobs/<?= htmlspecialchars($ev['recruitment_slug']) ?>" style="font-weight: 700; color: var(--text-primary);">
                      <?= htmlspecialchars($ev['recruitment_title']) ?>
                    </a>
                  <?php else: ?>
                    <strong style="color: var(--text-primary);"><?= htmlspecialchars($ev['event_title']) ?></strong>
                  <?php endif; ?>
                </td>
                <td>
                  <strong><?= htmlspecialchars($ev['event_title']) ?></strong>
                  <?php if (!empty($ev['details'])): ?>
                    <div style="font-size: 0.775rem; color: var(--text-muted); margin-top: 0.15rem;">
                      <?= htmlspecialchars(substr($ev['details'], 0, 75)) ?><?= strlen($ev['details']) > 75 ? '...' : '' ?>
                    </div>
                  <?php endif; ?>
                </td>
                <td style="font-weight: 700; color: var(--text-primary);">
                  <?= !empty($ev['event_date']) ? date('d M Y', strtotime($ev['event_date'])) : 'Declared' ?>
                </td>
                <td>
                  <?php
                    $lbl = '🏆 Result Declared';
                    if ($ev['event_type'] === 'FINAL_MERIT_LIST') $lbl = '📜 Merit List';
                    elseif ($ev['event_type'] === 'CUTOFF_RELEASED') $lbl = '📊 Cutoff Score';
                    elseif ($ev['event_type'] === 'ANSWER_KEY_RELEASED') $lbl = '🔑 Answer Key';
                  ?>
                  <span class="badge-active"><?= $lbl ?></span>
                </td>
                <td>
                  <?php $pdfUrl = !empty($ev['reference_url']) ? $ev['reference_url'] : $ev['official_website_url']; ?>
                  <?php if (!empty($pdfUrl)): ?>
                    <a href="<?= htmlspecialchars($pdfUrl) ?>" target="_blank" class="btn btn-primary btn-sm" style="padding: 0.3rem 0.65rem; font-size: 0.75rem;">
                      Download Merit PDF &rarr;
                    </a>
                  <?php else: ?>
                    <span style="color: var(--text-muted); font-size: 0.8rem;">PDF Pending</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Cutoff Marks Benchmarks Table -->
  <div class="content-box">
    <h3 class="content-box-title">
      📈 Official Category Cutoff Marks Benchmarks
    </h3>

    <div style="overflow-x: auto;">
      <table class="data-table">
        <thead>
          <tr>
            <th>Examination</th>
            <th>Year</th>
            <th>Category</th>
            <th>Cutoff Score</th>
            <th>Total Marks</th>
            <th>Benchmark Source</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($cutoffs as $c): ?>
            <tr>
              <td>
                <strong style="color: var(--text-primary);"><?= htmlspecialchars($c['exam_name']) ?></strong>
                <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($c['conducting_body']) ?></div>
              </td>
              <td><strong><?= $c['year'] ?></strong></td>
              <td><span class="badge-org" style="font-size: 0.7rem;"><?= htmlspecialchars($c['category']) ?></span></td>
              <td style="font-weight: 800; color: var(--primary-red); font-size: 1rem;"><?= number_format($c['cutoff_marks'], 2) ?></td>
              <td><?= number_format($c['total_marks'], 2) ?></td>
              <td><span class="badge-active">✓ Verified Official</span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
