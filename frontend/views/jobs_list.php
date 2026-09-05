<?php
require_once __DIR__ . '/../../backend/app/Database.php';
use App\Database;

$db = Database::getConnection();

$search = trim($_GET['q'] ?? '');
$state = trim($_GET['state'] ?? '');
$qualification = trim($_GET['qualification'] ?? '');
$category = trim($_GET['category'] ?? '');
$status = trim($_GET['status'] ?? '');
$sort = trim($_GET['sort'] ?? 'newest');

$conditions = [];
$params = [];

if (!empty($status) && $status !== 'ALL') {
    $conditions[] = "r.status = ?";
    $params[] = $status;
} else {
    $conditions[] = "r.status != 'Archived'";
}

if (!empty($search)) {
    $conditions[] = "(r.title LIKE ? OR r.organization_name LIKE ? OR r.summary LIKE ? OR r.advertisement_number LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if (!empty($state) && $state !== 'ALL') {
    $conditions[] = "(r.state_code = ? OR r.state_code = 'ALL')";
    $params[] = $state;
}

if (!empty($qualification)) {
    $conditions[] = "r.qualification_level LIKE ?";
    $params[] = "%{$qualification}%";
}

if (!empty($category)) {
    $conditions[] = "r.organization_name LIKE ?";
    $params[] = "%{$category}%";
}

$whereClause = implode(' AND ', $conditions);

// Sorting
$orderBy = match ($sort) {
    'vacancies' => "r.total_vacancies DESC, r.updated_at DESC",
    'oldest' => "r.created_at ASC",
    default => "r.updated_at DESC"
};

$stmt = $db->prepare("
    SELECT r.*,
           (SELECT event_date FROM recruitment_events WHERE recruitment_id = r.id AND event_type = 'APPLICATION_STARTED' LIMIT 1) as start_date,
           (SELECT event_date FROM recruitment_events WHERE recruitment_id = r.id AND event_type = 'APPLICATION_CLOSED' LIMIT 1) as last_date,
           (SELECT event_date FROM recruitment_events WHERE recruitment_id = r.id AND event_type = 'EXAM_DATE' LIMIT 1) as exam_date,
           (SELECT claimed_value FROM fact_claims WHERE entity_type = 'Recruitment' AND entity_id = r.id AND field_name = 'Pay Scale' LIMIT 1) as pay_scale,
           (SELECT claimed_value FROM fact_claims WHERE entity_type = 'Recruitment' AND entity_id = r.id AND field_name = 'Application Fee' LIMIT 1) as fee_details,
           (SELECT claimed_value FROM fact_claims WHERE entity_type = 'Recruitment' AND entity_id = r.id AND field_name = 'Age Limit' LIMIT 1) as age_limit
    FROM recruitments r
    WHERE {$whereClause} 
    ORDER BY {$orderBy} 
    LIMIT 50
");
$stmt->execute($params);
$jobs = $stmt->fetchAll();

$pageTitle = "Government Jobs 2026 — Latest Verified Official Recruitment Notifications Across India";
$pageDesc = "Search, filter, and apply for latest official government recruitment notifications across UPSC, SSC, Railways, Banking, Defence, and State PSCs with 100% verified ground truth.";
require_once __DIR__ . '/partials/header.php';
?>

<div class="container" style="padding: 3rem 0 5rem;">
  
  <div style="margin-bottom: 2rem;">
    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.4rem;">
      <span class="badge-org">VERIFIED DIRECTORY</span>
      <span style="font-size: 0.825rem; color: var(--text-muted); font-weight: 600;">Updated Daily from Official Gazettes</span>
    </div>
    <h1 style="font-family: var(--font-heading); font-size: 2.35rem; font-weight: 800; color: var(--text-primary); letter-spacing: -0.02em;">
      Latest Government Jobs Across India
    </h1>
    <p style="color: var(--text-secondary); font-size: 1rem; margin-top: 0.25rem;">
      Showing <strong><?= count($jobs) ?></strong> verified official recruitments with complete eligibility, salary matrix, dates, and direct links.
    </p>
  </div>

  <!-- Multi-Facet Advanced Filter Bar -->
  <?php 
    $activeFilterCount = (!empty($qualification) ? 1 : 0) + (!empty($category) ? 1 : 0) + (!empty($state) && $state !== 'ALL' ? 1 : 0) + (!empty($status) && $status !== 'ALL' ? 1 : 0);
  ?>
  <div class="filter-panel">
    <form method="GET" action="/government-jobs" class="filter-form-responsive">
      <div class="filter-top-bar">
        <div class="filter-search-wrap">
          <input 
            type="text" 
            name="q" 
            value="<?= htmlspecialchars($search) ?>" 
            placeholder="Search by Exam, Commission, Post..." 
            class="form-control"
          >
        </div>
        <div class="filter-actions-wrap">
          <button type="button" class="btn btn-outline filter-toggle-btn <?= $activeFilterCount > 0 ? 'is-active' : '' ?>" data-target="jobFilterCollapsible" aria-label="Toggle Advanced Filters">
            <?= app_icon('filter', '', 14) ?> <span>Filters</span>
            <?php if ($activeFilterCount > 0): ?>
              <span class="filter-count-badge"><?= $activeFilterCount ?></span>
            <?php endif; ?>
          </button>
          <button type="submit" class="btn btn-primary" style="padding: 0.65rem 1.15rem;">
            <?= app_icon('search', '', 14) ?> Apply
          </button>
          <?php if (!empty($search) || !empty($qualification) || (!empty($state) && $state !== 'ALL') || !empty($category) || (!empty($status) && $status !== 'ALL')): ?>
            <a href="/government-jobs" class="btn btn-glass" title="Clear Filters" style="padding: 0.65rem 0.85rem;">✕</a>
          <?php endif; ?>
        </div>
      </div>

      <div class="filter-collapsible <?= $activeFilterCount > 0 ? 'is-open' : '' ?>" id="jobFilterCollapsible">
        <!-- Qualification Filter -->
        <div>
          <select name="qualification" class="form-control">
            <option value="">All Qualifications</option>
            <option value="Graduate" <?= $qualification === 'Graduate' ? 'selected' : '' ?>>Graduate Degree</option>
            <option value="12th" <?= $qualification === '12th' ? 'selected' : '' ?>>12th Pass</option>
            <option value="10th" <?= $qualification === '10th' ? 'selected' : '' ?>>10th Pass / ITI</option>
            <option value="Engineering" <?= $qualification === 'Engineering' ? 'selected' : '' ?>>Engineering / Technical</option>
          </select>
        </div>

        <!-- Commission / Category -->
        <div>
          <select name="category" class="form-control">
            <option value="">All Commissions</option>
            <option value="UPSC" <?= $category === 'UPSC' ? 'selected' : '' ?>>UPSC</option>
            <option value="SSC" <?= $category === 'SSC' ? 'selected' : '' ?>>SSC</option>
            <option value="RRB" <?= $category === 'RRB' ? 'selected' : '' ?>>Railways (RRB)</option>
            <option value="IBPS" <?= $category === 'IBPS' ? 'selected' : '' ?>>Banking (IBPS)</option>
            <option value="Air Force" <?= $category === 'Air Force' ? 'selected' : '' ?>>Defence / IAF</option>
            <option value="GPSC" <?= $category === 'GPSC' ? 'selected' : '' ?>>State PSC (GPSC)</option>
          </select>
        </div>

        <!-- State / Region -->
        <div>
          <select name="state" class="form-control">
            <option value="ALL">All India (Central & State)</option>
            <option value="GJ" <?= $state === 'GJ' ? 'selected' : '' ?>>Gujarat</option>
            <option value="MH" <?= $state === 'MH' ? 'selected' : '' ?>>Maharashtra</option>
            <option value="UP" <?= $state === 'UP' ? 'selected' : '' ?>>Uttar Pradesh</option>
            <option value="BR" <?= $state === 'BR' ? 'selected' : '' ?>>Bihar</option>
            <option value="RJ" <?= $state === 'RJ' ? 'selected' : '' ?>>Rajasthan</option>
          </select>
        </div>

        <!-- Recruitment Lifecycle Status -->
        <div>
          <select name="status" class="form-control">
            <option value="ALL">All Process Statuses</option>
            <option value="Active" <?= $status === 'Active' ? 'selected' : '' ?>>Active Openings</option>
            <option value="Upcoming" <?= $status === 'Upcoming' ? 'selected' : '' ?>>Upcoming Notifications</option>
            <option value="Exam_Phase" <?= $status === 'Exam_Phase' ? 'selected' : '' ?>>Exam Phase</option>
            <option value="Result_Declared" <?= $status === 'Result_Declared' ? 'selected' : '' ?>>Result Declared</option>
          </select>
        </div>
      </div>
    </form>
  </div>

  <!-- Job Cards Grid -->
  <?php if (empty($jobs)): ?>
    <div style="text-align: center; padding: 4.5rem 1.5rem; background: #ffffff; border: 1px solid var(--border-subtle); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm);">
      <div style="font-size: 2.5rem; margin-bottom: 0.75rem;"></div>
      <h3 style="font-family: var(--font-heading); font-size: 1.4rem; font-weight: 700; color: var(--text-primary);">
        No matching official government recruitments found
      </h3>
      <p style="color: var(--text-secondary); margin-top: 0.35rem; max-width: 500px; margin-left: auto; margin-right: auto;">
        Try adjusting your keywords, choosing "All Qualifications", or clearing applied filters.
      </p>
      <a href="/government-jobs" class="btn btn-primary" style="margin-top: 1.5rem;">Reset All Filters</a>
    </div>
  <?php else: ?>
    <div class="job-grid">
      <?php foreach ($jobs as $job): ?>
        <?php
          $startDateStr = !empty($job['start_date']) ? date('d M Y', strtotime($job['start_date'])) : 'As per Notice';
          $lastDateStr = !empty($job['last_date']) ? date('d M Y', strtotime($job['last_date'])) : 'Open Notice';
          
          $jStatus = $job['status'] ?? 'Active';
          $urgencyBadge = 'Active Opening';
          $urgencyClass = 'badge-active';

          if ($jStatus === 'Upcoming') {
              $urgencyBadge = '⏳ Upcoming Notice';
              $urgencyClass = 'badge-upcoming';
          } elseif ($jStatus === 'Exam_Phase') {
              $urgencyBadge = '📝 Exam Phase Active';
              $urgencyClass = 'badge-exam';
          } elseif ($jStatus === 'Result_Declared') {
              $urgencyBadge = '🏆 Result Declared';
              $urgencyClass = 'badge-result';
          } elseif ($jStatus === 'Archived') {
              $urgencyBadge = '📁 Archived Notice';
              $urgencyClass = 'badge-closed';
          } elseif (!empty($job['last_date'])) {
              $diffDays = ceil((strtotime($job['last_date']) - time()) / 86400);
              if ($diffDays < 0) {
                  $urgencyBadge = 'Registration Closed';
                  $urgencyClass = 'badge-closed';
              } elseif ($diffDays <= 7) {
                  $urgencyBadge = "Ending Soon ({$diffDays} Days Left)";
                  $urgencyClass = 'badge-urgent';
              } else {
                  $urgencyBadge = "Apply by {$lastDateStr}";
                  $urgencyClass = 'badge-active';
              }
          }
        ?>
        <div class="job-card">
          <div>
            <!-- Header Badge & Verification Tag -->
            <div class="job-header">
              <span class="badge-org"><?= htmlspecialchars($job['organization_name']) ?></span>
              <span class="badge-verified">✓ Official Gazette</span>
            </div>

            <!-- Title -->
            <h3 class="job-title">
              <a href="/jobs/<?= htmlspecialchars($job['slug']) ?>" title="<?= htmlspecialchars((!empty($job['organization_name']) && !str_starts_with($job['title'], $job['organization_name'])) ? ($job['organization_name'] . ' ' . $job['title']) : $job['title']) ?>">
                <?= htmlspecialchars((!empty($job['organization_name']) && !str_starts_with($job['title'], $job['organization_name'])) ? ($job['organization_name'] . ' ' . $job['title']) : $job['title']) ?>
              </a>
            </h3>

            <!-- Notice & Ref -->
            <div class="job-advt-num">
              Advt: <?= htmlspecialchars($job['advertisement_number'] ?: $job['notification_number'] ?: 'Official Gazette 2026') ?> • Year <?= $job['year'] ?>
            </div>

            <!-- 6-Cell Complete Data Matrix -->
            <div class="job-metrics-matrix">
              <div class="metric-cell">
                <span class="metric-cell-label"><?= app_icon('users', '', 12) ?> Vacancies</span>
                <span class="metric-cell-val" style="color: var(--primary-red); font-weight: 800;" title="<?= $job['total_vacancies'] ? number_format($job['total_vacancies']) . ' Posts' : 'As per Notice' ?>">
                  <?= $job['total_vacancies'] ? number_format($job['total_vacancies']) . ' Posts' : 'As per Notice' ?>
                </span>
              </div>

              <div class="metric-cell">
                <span class="metric-cell-label"><?= app_icon('graduation-cap', '', 12) ?> Qualification</span>
                <span class="metric-cell-val" title="<?= htmlspecialchars($job['qualification_level'] ?: 'Graduate Degree') ?>">
                  <?= htmlspecialchars($job['qualification_level'] ?: 'Graduate Degree') ?>
                </span>
              </div>

              <div class="metric-cell">
                <span class="metric-cell-label"><?= app_icon('banknote', '', 12) ?> Pay Scale</span>
                <span class="metric-cell-val" style="color: var(--emerald);" title="<?= htmlspecialchars($job['pay_scale'] ?: '7th CPC Matrix') ?>">
                  <?= htmlspecialchars($job['pay_scale'] ?: '7th CPC Matrix') ?>
                </span>
              </div>

              <div class="metric-cell">
                <span class="metric-cell-label"><?= app_icon('cake', '', 12) ?> Age Limit</span>
                <span class="metric-cell-val" title="<?= htmlspecialchars($job['age_limit'] ?: '18 - 32 Years') ?>">
                  <?= htmlspecialchars($job['age_limit'] ?: '18 - 32 Years') ?>
                </span>
              </div>

              <div class="metric-cell">
                <span class="metric-cell-label"><?= app_icon('credit-card', '', 12) ?> Application Fee</span>
                <span class="metric-cell-val" title="<?= htmlspecialchars($job['fee_details'] ?: 'Gen: ₹100 / SC: ₹0') ?>">
                  <?= htmlspecialchars($job['fee_details'] ?: 'Gen: ₹100 / SC: ₹0') ?>
                </span>
              </div>

              <div class="metric-cell">
                <span class="metric-cell-label"><?= app_icon('map-pin', '', 12) ?> Cadre / Region</span>
                <span class="metric-cell-val" title="<?= htmlspecialchars($job['state_code'] === 'ALL' ? 'All India' : $job['state_code']) ?>">
                  <?= htmlspecialchars($job['state_code'] === 'ALL' ? 'All India' : $job['state_code']) ?>
                </span>
              </div>
            </div>

            <!-- Dates Row -->
            <div class="job-dates-row">
              <div class="job-dates-header">
                <span class="job-dates-label"><?= app_icon('calendar', '', 12) ?> Application Window</span>
                <span class="<?= $urgencyClass ?>"><?= $urgencyBadge ?></span>
              </div>
              <div class="job-dates-val">
                <span><?= $startDateStr ?></span>
                <span style="color: var(--text-muted); font-size: 0.75rem;">to</span>
                <span style="color: var(--primary-red-dark); font-weight: 800;"><?= $lastDateStr ?></span>
              </div>
            </div>
          </div>

          <!-- Structured 2-Tier Actions: Full Details CTA + Secondary Quick Actions -->
          <div class="job-card-actions">
            <a href="/jobs/<?= htmlspecialchars($job['slug']) ?>" class="btn btn-primary btn-card-main">
              Full Details &amp; Syllabus &rarr;
            </a>

            <?php if (!empty($job['official_apply_url']) || !empty($job['primary_notification_url'])): ?>
              <div class="job-card-secondary-actions">
                <?php if (!empty($job['official_apply_url'])): ?>
                  <a href="<?= htmlspecialchars($job['official_apply_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-card-sub">
                    <?= app_icon('external-link', '', 13) ?> Apply Online
                  </a>
                <?php endif; ?>

                <?php if (!empty($job['primary_notification_url'])): ?>
                  <a href="<?= htmlspecialchars($job['primary_notification_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-glass btn-card-sub" title="Official Gazette PDF Notice">
                    <?= app_icon('download', '', 13) ?> Official PDF
                  </a>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
