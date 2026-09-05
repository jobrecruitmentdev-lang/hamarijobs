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
  <!-- Hall Ticket Advisory & Verification Note (Backlink 1) -->
  <div style="background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); border-left: 4px solid var(--primary-red); border-radius: var(--radius-sm); padding: 1rem 1.25rem; margin-bottom: 1.5rem; font-size: 0.9rem; color: var(--text-secondary); line-height: 1.6;">
    <strong style="color: var(--text-primary);">📢 Candidate Advisory:</strong> E-admit cards and hall tickets must be downloaded prior to the reporting deadline. For authenticated examination schedules, syllabus weightage, and reporting instructions, refer to the <a href="https://jobrecruitment.in/" target="_blank" rel="noopener" style="color: var(--primary-red); font-weight: 700; text-decoration: underline;">Government Job Recruitment</a> portal.
  </div>

  <!-- Filter Bar -->
  <div class="filter-panel">
    <form method="GET" action="/admit-cards" class="filter-grid-3">
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
        <button type="submit" class="btn btn-primary" style="flex: 1;"><?= app_icon('search', '', 14) ?> Filter</button>
        <?php if (!empty($search) || !empty($category)): ?>
          <a href="/admit-cards" class="btn btn-glass" title="Reset Filters">✕</a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- Admit Cards Table / Adaptive Cards -->
  <div class="content-box">
    <div class="table-scroll-wrapper">
      <div class="table-scroll-hint">Swipe sideways to view full columns &rarr;</div>
      <table class="data-table responsive-adaptive-table">
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
                $rawStatus = strtoupper(trim($ev['status'] ?? ''));
                $now = time();
                $eventTimestamp = !empty($ev['event_date']) ? strtotime($ev['event_date']) : null;
                $isFuture = $eventTimestamp && $eventTimestamp > $now;
                $isExpired = $eventTimestamp && $eventTimestamp < ($now - 14 * 86400); // 14 days past

                if ($isExpired) {
                    $statusBadgeClass = 'badge-closed';
                    $statusLabel = '🔒 Exam Concluded';
                    $btnLabel = 'Exam Concluded';
                    $btnClass = 'btn btn-glass btn-sm';
                } elseif ($rawStatus === 'RELEASED' && !$isFuture) {
                    $statusBadgeClass = 'badge-active';
                    $statusLabel = '✓ Available Now';
                    $btnLabel = 'Download Hall Ticket &rarr;';
                    $btnClass = 'btn btn-primary btn-sm';
                } elseif ($ev['event_type'] === 'EXAM_DATE' || $rawStatus === 'SCHEDULED') {
                    $statusBadgeClass = 'badge-org';
                    $statusLabel = '📅 Exam Date Announced';
                    $btnLabel = 'View Exam Schedule &nearr;';
                    $btnClass = 'btn btn-glass btn-sm';
                } elseif ($rawStatus === 'CITY_SLIP') {
                    $statusBadgeClass = 'badge-org';
                    $statusLabel = '🗺️ City Slip Out';
                    $btnLabel = 'Check City Slip &nearr;';
                    $btnClass = 'btn btn-primary btn-sm';
                } elseif ($rawStatus === 'POSTPONED') {
                    $statusBadgeClass = 'badge-closed';
                    $statusLabel = '⚠️ Postponed';
                    $btnLabel = 'Check Notice &nearr;';
                    $btnClass = 'btn btn-glass btn-sm';
                } else {
                    // Future / Upcoming Admit Cards
                    $statusBadgeClass = 'badge-urgent';
                    $statusLabel = '⏳ Releasing Soon';
                    $btnLabel = 'Official Portal &nearr;';
                    $btnClass = 'btn btn-glass btn-sm';
                }

                $dateFormatted = !empty($ev['event_date']) ? date('d M Y (l)', strtotime($ev['event_date'])) : 'To Be Announced';
                $downloadUrl = !empty($ev['reference_url']) ? $ev['reference_url'] : ($ev['official_apply_url'] ?? $ev['official_website_url']);
              ?>
              <tr>
                <td data-label="Commission / Exam">
                  <div style="display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap;">
                    <span class="badge-org" style="font-size: 0.65rem;"><?= htmlspecialchars($ev['organization_name']) ?></span>
                  </div>
                  <?php if (!empty($ev['recruitment_slug'])): ?>
                    <a href="/jobs/<?= htmlspecialchars($ev['recruitment_slug']) ?>" style="font-weight: 700; color: var(--text-primary); display: block; margin-top: 0.2rem;">
                      <?= htmlspecialchars($ev['recruitment_title']) ?>
                    </a>
                  <?php else: ?>
                    <strong style="color: var(--text-primary); display: block; margin-top: 0.2rem;"><?= htmlspecialchars($ev['event_title']) ?></strong>
                  <?php endif; ?>
                </td>
                <td data-label="Milestone Event">
                  <strong><?= htmlspecialchars($ev['event_title']) ?></strong>
                  <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars(str_replace('_', ' ', $ev['event_type'])) ?></div>
                </td>
                <td data-label="Release Date" style="font-weight: 700; color: var(--text-primary);">
                  <?= $dateFormatted ?>
                </td>
                <td data-label="Status">
                  <span class="<?= $statusBadgeClass ?>"><?= $statusLabel ?></span>
                </td>
                <td data-label="Download Action">
                  <?php if (!empty($downloadUrl)): ?>
                    <a href="<?= htmlspecialchars($downloadUrl) ?>" target="_blank" rel="noopener noreferrer" class="<?= $btnClass ?>" style="padding: 0.5rem 0.85rem; font-size: 0.825rem;">
                      <?= $btnLabel ?>
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

  <!-- Hall Ticket Guidelines & Verification Desk (Backlink 2) -->
  <div class="content-box" style="margin-top: 2rem; background: linear-gradient(135deg, #ffffff 0%, var(--bg-surface-elevated) 100%); border: 1px solid var(--border-subtle); padding: 1.5rem 2rem;">
    <h3 style="font-family: var(--font-heading); font-size: 1.15rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.5rem;">
      🔍 Verification Protocol & City Intimation Discrepancies
    </h3>
    <p style="font-size: 0.9rem; color: var(--text-secondary); line-height: 1.7; margin: 0;">
      Carefully verify your roll number, designated test shift, and center address on your hall ticket. In case of any anomaly in personal details or photograph, candidates should immediately report to the respective recruitment authority. Real-time hall ticket releases and exam updates across all central and state boards can be monitored on <a href="https://jobrecruitment.in/" target="_blank" rel="noopener" style="color: var(--primary-red); font-weight: 700; text-decoration: underline;">Job Recruitment India</a>.
    </p>
  </div>

</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
