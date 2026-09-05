<?php
require_once __DIR__ . '/../../backend/app/Database.php';
use App\Database;

$slug = trim($_GET['slug'] ?? '');
$db = Database::getConnection();

$stmt = $db->prepare("SELECT * FROM commissions WHERE slug = ? AND is_active = 1");
$stmt->execute([$slug]);
$comm = $stmt->fetch();

if (!$comm) {
    header("HTTP/1.0 404 Not Found");
    $pageTitle = "404 — Commission Not Found";
    require_once __DIR__ . '/partials/header.php';
    echo "<div class='container' style='padding: 6rem 0; text-align: center;'><h2>404 — Commission Dossier Not Found</h2><p style='color: var(--text-secondary); margin: 1rem 0 2rem;'>The requested government recruiting authority could not be located in our active gazette directory.</p><a href='/commissions' class='btn btn-primary'>Browse All Commissions</a></div>";
    require_once __DIR__ . '/partials/footer.php';
    exit;
}

$comm['short'] = $comm['short_name'];
$filterKeyword = $comm['filter_keyword'] ?: $comm['short_name'];

if (!$comm) {
    http_response_code(404);
    $pageTitle = "Commission Not Found — Government Recruitment Intelligence";
    require_once __DIR__ . '/partials/header.php';
    echo "<div class='container' style='padding: 6rem 0; text-align: center;'><h2>404 — Commission Dossier Not Found</h2><p style='color: var(--text-secondary); margin: 1rem 0 2rem;'>The requested government recruiting commission is not cataloged.</p><a href='/commissions' class='btn btn-primary'>Browse All Commissions</a></div>";
    require_once __DIR__ . '/partials/footer.php';
    exit;
}

// Fetch active recruitments for this commission
$jobStmt = $db->prepare("
    SELECT r.*,
           (SELECT event_date FROM recruitment_events WHERE recruitment_id = r.id AND event_type = 'APPLICATION_STARTED' LIMIT 1) as start_date,
           (SELECT event_date FROM recruitment_events WHERE recruitment_id = r.id AND event_type = 'APPLICATION_CLOSED' LIMIT 1) as last_date,
           (SELECT claimed_value FROM fact_claims WHERE entity_type = 'Recruitment' AND entity_id = r.id AND field_name = 'Pay Scale' LIMIT 1) as pay_scale,
           (SELECT claimed_value FROM fact_claims WHERE entity_type = 'Recruitment' AND entity_id = r.id AND field_name = 'Application Fee' LIMIT 1) as fee_details,
           (SELECT claimed_value FROM fact_claims WHERE entity_type = 'Recruitment' AND entity_id = r.id AND field_name = 'Age Limit' LIMIT 1) as age_limit
    FROM recruitments r
    WHERE (r.organization_name LIKE ? OR r.title LIKE ?) AND r.status = 'Active'
    ORDER BY r.updated_at DESC
");
$jobStmt->execute(["%{$comm['filter_keyword']}%", "%{$comm['filter_keyword']}%"]);
$jobs = $jobStmt->fetchAll();

// Fetch related exams
$examStmt = $db->prepare("SELECT * FROM exams WHERE conducting_body LIKE ? OR short_name LIKE ? OR category LIKE ?");
$examStmt->execute(["%{$comm['filter_keyword']}%", "%{$comm['filter_keyword']}%", "%{$comm['filter_keyword']}%"]);
$exams = $examStmt->fetchAll();

$pageTitle = "{$comm['name']} Recruitment Dossier 2026 — Verified Notices, Exam Schemes, Syllabus";
$pageDesc = "Complete intelligence dossier for {$comm['name']} ({$comm['short']}). Access active official job openings, exam patterns, cutoff trends, and official portals.";
$canonicalUrl = "https://hamarijobs.com/commissions/{$slug}";

require_once __DIR__ . '/partials/header.php';
?>

<div class="container" style="padding: 2.5rem 0 5rem;">
  
  <!-- Breadcrumb -->
  <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.5rem;">
    <a href="/" style="color: var(--text-secondary);">Home</a> &nbsp;/&nbsp; 
    <a href="/commissions" style="color: var(--text-secondary);">Commissions</a> &nbsp;/&nbsp; 
    <span style="color: var(--primary-red); font-weight: 600;"><?= htmlspecialchars($comm['short']) ?></span>
  </div>

  <!-- Commission Hero Dossier -->
  <div class="dossier-hero">
    <div class="dossier-header-top">
      <div style="display: flex; align-items: center; gap: 0.75rem;">
        <span class="badge-org"><?= htmlspecialchars($comm['category']) ?></span>
        <span class="badge-verified">✓ 100% Official Verified Body</span>
      </div>
      <a href="<?= htmlspecialchars($comm['website']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-sm">
        Official Commission Website &rarr;
      </a>
    </div>

    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap;">
      <div class="commission-icon" style="width: 58px; height: 58px; font-size: 1.75rem; margin: 0;">
        <?= $comm['emblem'] ?>
      </div>
      <div>
        <h1 class="dossier-title" style="margin-bottom: 0.2rem;">
          <?= htmlspecialchars($comm['name']) ?>
        </h1>
        <div style="font-size: 0.85rem; color: var(--text-muted);">
          Official Headquarters: <strong><?= htmlspecialchars($comm['hq']) ?></strong>
        </div>
      </div>
    </div>

    <p class="dossier-summary">
      <?= htmlspecialchars($comm['description']) ?> Candidates tracking official employment notices can review <a href="https://jobrecruitment.in/" target="_blank" rel="noopener" style="color: var(--primary-red); font-weight: 700; text-decoration: underline;"><?= htmlspecialchars($comm['name']) ?> Recruitment</a> updates, examination schemes, and eligibility standards.
    </p>

    <!-- Quick Stats Row -->
    <div class="preview-grid" style="margin-top: 1.5rem; max-width: 720px;">
      <div class="preview-item">
        <div class="preview-label">Active Vacancies</div>
        <div class="preview-val" style="color: var(--primary-red);"><?= count($jobs) ?> Active Notices</div>
      </div>
      <div class="preview-item">
        <div class="preview-label">Annual Candidate Base</div>
        <div class="preview-val" style="color: var(--blue);"><?= htmlspecialchars($comm['annual_candidates']) ?></div>
      </div>
      <div class="preview-item" style="grid-column: 1 / -1;">
        <div class="preview-label">Standard Selection Scheme</div>
        <div class="preview-val" style="font-size: 0.95rem; font-weight: 600;"><?= htmlspecialchars($comm['selection_phases']) ?></div>
      </div>
    </div>
  </div>

  <!-- Active Job Openings Grid for this Commission -->
  <div style="margin-bottom: 3.5rem;">
    <div class="section-title-wrap">
      <div>
        <h2 class="section-title">
          Active <span><?= htmlspecialchars($comm['short']) ?> Job Notifications</span>
        </h2>
        <p class="section-subtitle">
          Directly verified from the official <?= htmlspecialchars($comm['short']) ?> gazette publication
        </p>
      </div>
    </div>

    <!-- Commission Career Advisory Banner (Backlink 2) -->
    <div style="background: var(--bg-surface-elevated); border-left: 4px solid var(--primary-red); border-radius: var(--radius-sm); padding: 1rem 1.25rem; margin-bottom: 1.75rem; border: 1px solid var(--border-subtle); border-left-width: 4px;">
      <div style="font-size: 0.9rem; color: var(--text-secondary); line-height: 1.6;">
        <strong style="color: var(--text-primary);">📌 Recruitment Intelligence Advisory:</strong> Real-time alerts, syllabus breakdowns, and cut-off trends for all <?= htmlspecialchars($comm['short']) ?> posts are archived on the <a href="https://jobrecruitment.in/" target="_blank" rel="noopener" style="color: var(--primary-red); font-weight: 700; text-decoration: underline;">Government Job Recruitment</a> portal for competitive exam aspirants.
      </div>
    </div>

    <?php if (empty($jobs)): ?>
      <div class="content-box" style="text-align: center; padding: 3rem 1.5rem;">
        <p style="color: var(--text-secondary);">No active recruitment notices found for <?= htmlspecialchars($comm['short']) ?> at this moment. Check back soon.</p>
      </div>
    <?php else: ?>
      <div class="job-grid">
        <?php foreach ($jobs as $job): ?>
          <?php
            $startDateStr = !empty($job['start_date']) ? date('d M Y', strtotime($job['start_date'])) : 'As per Notice';
            $lastDateStr = !empty($job['last_date']) ? date('d M Y', strtotime($job['last_date'])) : 'Open Notice';
          ?>
          <div class="job-card">
            <div>
              <div class="job-header">
                <span class="badge-org"><?= htmlspecialchars($job['organization_name']) ?></span>
                <span class="badge-verified">✓ Verified Gazette</span>
              </div>

              <h3 class="job-title">
                <a href="/jobs/<?= htmlspecialchars($job['slug']) ?>"><?= htmlspecialchars($job['title']) ?></a>
              </h3>

              <div class="job-advt-num">
                Advt: <?= htmlspecialchars($job['advertisement_number'] ?: 'Official Ref') ?> • Year <?= $job['year'] ?>
              </div>

              <div class="job-metrics-matrix">
                <div class="metric-cell">
                  <span class="metric-cell-label">Vacancies</span>
                  <span class="metric-cell-val" style="color: var(--primary-red); font-weight: 800;" title="<?= $job['total_vacancies'] ? number_format($job['total_vacancies']) . ' Posts' : 'As per Notice' ?>"><?= $job['total_vacancies'] ? number_format($job['total_vacancies']) . ' Posts' : 'As per Notice' ?></span>
                </div>
                <div class="metric-cell">
                  <span class="metric-cell-label">Qualification</span>
                  <span class="metric-cell-val" title="<?= htmlspecialchars($job['qualification_level'] ?: 'Graduate Degree') ?>"><?= htmlspecialchars($job['qualification_level'] ?: 'Graduate Degree') ?></span>
                </div>
                <div class="metric-cell">
                  <span class="metric-cell-label">Pay Scale</span>
                  <span class="metric-cell-val" style="color: var(--emerald);" title="<?= htmlspecialchars($job['pay_scale'] ?: '7th CPC Matrix') ?>"><?= htmlspecialchars($job['pay_scale'] ?: '7th CPC Matrix') ?></span>
                </div>
                <div class="metric-cell">
                  <span class="metric-cell-label">Age Limit</span>
                  <span class="metric-cell-val" title="<?= htmlspecialchars($job['age_limit'] ?: '18 - 32 Years') ?>"><?= htmlspecialchars($job['age_limit'] ?: '18 - 32 Years') ?></span>
                </div>
              </div>

              <div class="job-dates-row">
                <div class="job-dates-header">
                  <span class="job-dates-label">Application Window</span>
                  <span class="badge-active">Active</span>
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
                      Apply Online
                    </a>
                  <?php endif; ?>

                  <?php if (!empty($job['primary_notification_url'])): ?>
                    <a href="<?= htmlspecialchars($job['primary_notification_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-glass btn-card-sub" title="Download Official PDF Notice">
                      Official PDF
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

  <!-- Linked Examination Intelligence Hubs -->
  <?php if (!empty($exams)): ?>
    <div style="margin-top: 3.5rem;">
      <div class="section-title-wrap">
        <div>
          <h2 class="section-title">
            Linked <span><?= htmlspecialchars($comm['short']) ?> Examination Hubs</span>
          </h2>
          <p class="section-subtitle">Schemes, syllabus weightages, and previous year cutoff records</p>
        </div>
      </div>

      <div class="exam-grid">
        <?php foreach ($exams as $ex): ?>
          <div class="exam-card">
            <div>
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                <span class="badge-org"><?= htmlspecialchars($ex['category']) ?></span>
                <span style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($ex['frequency']) ?></span>
              </div>
              <h3 style="font-family: var(--font-heading); font-size: 1.25rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.4rem;">
                <?= htmlspecialchars($ex['name']) ?>
              </h3>
              <p style="font-size: 0.875rem; color: var(--text-secondary); line-height: 1.55; margin-bottom: 1.25rem;">
                <?= htmlspecialchars(substr($ex['overview'], 0, 160)) ?>...
              </p>
            </div>
            <div style="border-top: 1px solid var(--border-subtle); padding-top: 1rem;">
              <a href="/exams/<?= htmlspecialchars($ex['slug']) ?>" class="btn btn-primary btn-sm" style="width: 100%; text-align: center;">
                View Pattern, Syllabus & Cutoffs &rarr;
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
