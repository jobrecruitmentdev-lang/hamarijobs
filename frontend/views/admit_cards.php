<?php
require_once __DIR__ . '/../../backend/app/Database.php';
use App\Database;

$db = Database::getConnection();

$search = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');

$conditions = ["re.event_type IN ('ADMIT_CARD_RELEASED', 'EXAM_DATE', 'CORRECTION_WINDOW_OPENED')"];
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
    SELECT re.*, r.title as recruitment_title, r.slug as recruitment_slug, COALESCE(r.organization_name, re.organization_name, 'Government of India') as organization_name, COALESCE(r.official_website_url, re.reference_url) as official_website_url, r.official_apply_url
    FROM recruitment_events re
    LEFT JOIN recruitments r ON re.recruitment_id = r.id
    WHERE {$whereClause}
    ORDER BY re.event_date DESC, re.id DESC
");
$stmt->execute($params);
$events = $stmt->fetchAll();

$pageTitle = "Government Exam Admit Cards & Hall Tickets 2026 — Direct Download Links & Exam Dates";
$pageDesc = "Download official government examination admit cards, hall tickets, city intimation slips for UPSC, SSC, Railways, Banking, and State PSC exams.";
require_once __DIR__ . '/partials/header.php';
?>

<div class="container" style="padding: 3rem 0 5rem;">
  
  <div style="margin-bottom: 2.5rem;">
    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.4rem;">
      <span class="badge-org">ADMIT CARDS TRACKER</span>
      <span style="font-size: 0.825rem; color: var(--text-muted); font-weight: 600;">Verified Hall Ticket Release Dates</span>
    </div>
    <h1 style="font-family: var(--font-heading); font-size: 2.35rem; font-weight: 800; color: var(--text-primary); letter-spacing: -0.02em;">
      Government Exam Admit Cards 2026
    </h1>
    <p style="color: var(--text-secondary); font-size: 1rem; margin-top: 0.25rem;">
      Track live admit card releases, exam city intimation slips, and direct official download links.
    </p>
  </div>

  <!-- Filter Bar -->
  <div class="filter-panel">
    <form method="GET" action="/admit-cards" class="filter-grid" style="grid-template-columns: 2fr 1fr auto;">
      <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search by Exam, Commission, Post..." class="form-control">
      
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
        <button type="submit" class="btn btn-primary">Filter</button>
        <?php if (!empty($search) || !empty($category)): ?>
          <a href="/admit-cards" class="btn btn-glass">Reset</a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- Admit Cards Table -->
  <div class="content-box">
    <div style="overflow-x: auto;">
      <table class="data-table">
        <thead>
          <tr>
            <th>Commission / Exam</th>
            <th>Milestone Event</th>
            <th>Release Date</th>
            <th>Status</th>
            <th>Official Download Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($events)): ?>
            <tr>
              <td colspan="5" style="text-align: center; padding: 3rem; color: var(--text-secondary);">
                No active admit cards match your filter criteria.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($events as $ev): ?>
              <?php
                $isReleased = !empty($ev['event_date']) && strtotime($ev['event_date']) <= time();
                $dateFormatted = !empty($ev['event_date']) ? date('d M Y (l)', strtotime($ev['event_date'])) : 'To Be Announced';
                $downloadUrl = !empty($ev['reference_url']) ? $ev['reference_url'] : ($ev['official_apply_url'] ?? $ev['official_website_url']);
              ?>
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
                  <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars(str_replace('_', ' ', $ev['event_type'])) ?></div>
                </td>
                <td style="font-weight: 700; color: var(--text-primary);">
                  <?= $dateFormatted ?>
                </td>
                <td>
                  <?php if ($isReleased): ?>
                    <span class="badge-active">Available Now</span>
                  <?php else: ?>
                    <span class="badge-urgent">⏳ Releasing Soon</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if (!empty($downloadUrl)): ?>
                    <a href="<?= htmlspecialchars($downloadUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-sm" style="padding: 0.35rem 0.75rem; font-size: 0.8rem;">
                      Download Hall Ticket &rarr;
                    </a>
                  <?php else: ?>
                    <span style="color: var(--text-muted); font-size: 0.8rem;">Notice Pending</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
