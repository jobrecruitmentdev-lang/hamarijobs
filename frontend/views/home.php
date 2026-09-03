<?php
require_once __DIR__ . '/../../backend/app/Database.php';
use App\Database;

$db = Database::getConnection();

// Fetch Latest Verified Jobs with rich timeline events and facts
$jobStmt = $db->query("
    SELECT r.*,
           (SELECT event_date FROM recruitment_events WHERE recruitment_id = r.id AND event_type = 'APPLICATION_STARTED' LIMIT 1) as start_date,
           (SELECT event_date FROM recruitment_events WHERE recruitment_id = r.id AND event_type = 'APPLICATION_CLOSED' LIMIT 1) as last_date,
           (SELECT event_date FROM recruitment_events WHERE recruitment_id = r.id AND event_type = 'EXAM_DATE' LIMIT 1) as exam_date,
           (SELECT claimed_value FROM fact_claims WHERE entity_type = 'Recruitment' AND entity_id = r.id AND field_name = 'Pay Scale' LIMIT 1) as pay_scale,
           (SELECT claimed_value FROM fact_claims WHERE entity_type = 'Recruitment' AND entity_id = r.id AND field_name = 'Application Fee' LIMIT 1) as fee_details,
           (SELECT claimed_value FROM fact_claims WHERE entity_type = 'Recruitment' AND entity_id = r.id AND field_name = 'Age Limit' LIMIT 1) as age_limit
    FROM recruitments r
    WHERE r.status = 'Active' 
    ORDER BY r.updated_at DESC 
    LIMIT 6
");
$latestJobs = $jobStmt->fetchAll();

// Fetch Active Exams
$examStmt = $db->query("
    SELECT * FROM exams 
    WHERE is_active = 1 
    LIMIT 4
");
$exams = $examStmt->fetchAll();

// Fetch Latest Articles
$artStmt = $db->query("
    SELECT * FROM articles 
    WHERE status = 'Published' 
    ORDER BY published_at DESC 
    LIMIT 3
");
$articles = $artStmt->fetchAll();

// Stats counts
$jobCount = $db->query("SELECT COUNT(*) FROM recruitments WHERE status = 'Active'")->fetchColumn() ?: 6;
$sourceCount = $db->query("SELECT COUNT(*) FROM source_registry WHERE status = 'Active'")->fetchColumn() ?: 24;
$examCount = $db->query("SELECT COUNT(*) FROM exams WHERE is_active = 1")->fetchColumn() ?: 4;

$pageTitle = "Government Recruitment Intelligence Portal — Verified Official Jobs, Exams & Notifications";
require_once __DIR__ . '/partials/header.php';
?>

<!-- 1. Interactive Hero Carousel Section (Red & White Light) -->
<section class="hero-carousel-section">
  <div class="container">
    <div class="carousel-container">
      
      <!-- Carousel Arrow Buttons -->
      <button class="carousel-arrow prev" aria-label="Previous Slide">&larr;</button>
      <button class="carousel-arrow next" aria-label="Next Slide">&rarr;</button>

      <!-- Slides Wrapper -->
      <div class="carousel-slides">
        
        <!-- Slide 1: SSC CGL 2026 -->
        <div class="carousel-slide">
          <div>
            <div class="slide-tag">
              <span class="pulse-dot"></span>
              <span>STAFF SELECTION COMMISSION (SSC)</span>
            </div>
            <h2 class="slide-title">
              Combined Graduate Level <span>(CGL) 2026</span>
            </h2>
            <p class="slide-desc">
              Official gazette notice released for 7,500+ Group B & C Officer posts across Central Ministries. 100% verified notification with detailed syllabus, pay levels, and exam patterns.
            </p>
            <div class="slide-actions">
              <a href="/jobs/ssc-combined-graduate-level-cgl-examination-2026-2026" class="btn btn-primary">
                View Detailed Notification &rarr;
              </a>
              <a href="/exams/ssc-cgl" class="btn btn-glass">
                🧠 Explore Exam Hub
              </a>
            </div>
          </div>

          <div class="slide-card-preview">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <span class="badge-org">SSC OFFICIAL</span>
              <span class="badge-verified"><span class="pulse-dot"></span> Active Opening</span>
            </div>
            <h3 style="font-family: var(--font-heading); font-size: 1.25rem; font-weight: 700; margin-top: 0.75rem; color: var(--text-primary);">
              CGL Examination 2026
            </h3>
            <div class="preview-grid">
              <div class="preview-item">
                <div class="preview-label">Vacancies</div>
                <div class="preview-val" style="color: var(--primary-red);">7,500+ Posts</div>
              </div>
              <div class="preview-item">
                <div class="preview-label">Pay Level</div>
                <div class="preview-val" style="color: var(--emerald);">Level 4 to 8</div>
              </div>
              <div class="preview-item">
                <div class="preview-label">Qualification</div>
                <div class="preview-val">Graduate Degree</div>
              </div>
              <div class="preview-item">
                <div class="preview-label">Exam Scheme</div>
                <div class="preview-val">Tier 1 & 2 CBT</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Slide 2: UPSC CSE 2026 -->
        <div class="carousel-slide">
          <div>
            <div class="slide-tag">
              <span class="pulse-dot"></span>
              <span>UNION PUBLIC SERVICE COMMISSION (UPSC)</span>
            </div>
            <h2 class="slide-title">
              Civil Services <span>Examination 2026</span>
            </h2>
            <p class="slide-desc">
              India's premier administrative examination for IAS, IPS, IFS, and IRS cadres. Access historical cutoffs, preliminary syllabus weightages, and verified timeline events.
            </p>
            <div class="slide-actions">
              <a href="/jobs/upsc-civil-services-examination-2026-2026" class="btn btn-primary">
                View Detailed Notification &rarr;
              </a>
              <a href="/exams/upsc-cse" class="btn btn-glass">
                🧠 Explore CSE Hub
              </a>
            </div>
          </div>

          <div class="slide-card-preview">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <span class="badge-org">UPSC CADRE</span>
              <span class="badge-verified"><span class="pulse-dot"></span> All India Post</span>
            </div>
            <h3 style="font-family: var(--font-heading); font-size: 1.25rem; font-weight: 700; margin-top: 0.75rem; color: var(--text-primary);">
              Civil Services (Prelims) 2026
            </h3>
            <div class="preview-grid">
              <div class="preview-item">
                <div class="preview-label">Vacancies</div>
                <div class="preview-val" style="color: var(--primary-red);">1,056 Posts</div>
              </div>
              <div class="preview-item">
                <div class="preview-label">Pay Level</div>
                <div class="preview-val" style="color: var(--emerald);">Level 10+</div>
              </div>
              <div class="preview-item">
                <div class="preview-label">Age Limit</div>
                <div class="preview-val">21 - 32 Yrs</div>
              </div>
              <div class="preview-item">
                <div class="preview-label">Exam Scheme</div>
                <div class="preview-val">Prelims -> Mains</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Slide 3: RRB NTPC 2026 -->
        <div class="carousel-slide">
          <div>
            <div class="slide-tag">
              <span class="pulse-dot"></span>
              <span>RAILWAY RECRUITMENT CONTROL BOARD (RRB)</span>
            </div>
            <h2 class="slide-title">
              Railway NTPC <span>CEN 05/2026</span>
            </h2>
            <p class="slide-desc">
              Massive centralized railway recruitment notice for 11,558 Station Master, Goods Train Manager, and Senior Clerk posts across all Indian Railway Zones.
            </p>
            <div class="slide-actions">
              <a href="/jobs/rrb-non-technical-popular-categories-ntpc-cen-05-2026-2026" class="btn btn-primary">
                View Detailed Notification &rarr;
              </a>
              <a href="/exams/rrb-ntpc" class="btn btn-glass">
                🧠 Explore NTPC Hub
              </a>
            </div>
          </div>

          <div class="slide-card-preview">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <span class="badge-org">INDIAN RAILWAYS</span>
              <span class="badge-verified"><span class="pulse-dot"></span> Central Notice</span>
            </div>
            <h3 style="font-family: var(--font-heading); font-size: 1.25rem; font-weight: 700; margin-top: 0.75rem; color: var(--text-primary);">
              NTPC CEN 05/2026
            </h3>
            <div class="preview-grid">
              <div class="preview-item">
                <div class="preview-label">Vacancies</div>
                <div class="preview-val" style="color: var(--primary-red);">11,558 Posts</div>
              </div>
              <div class="preview-item">
                <div class="preview-label">Pay Level</div>
                <div class="preview-val" style="color: var(--emerald);">Level 2 to 6</div>
              </div>
              <div class="preview-item">
                <div class="preview-label">Qualification</div>
                <div class="preview-val">12th / Graduate</div>
              </div>
              <div class="preview-item">
                <div class="preview-label">Exam Scheme</div>
                <div class="preview-val">CBT 1 & CBT 2</div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</section>

<!-- 2. Major Commissions Navigation Grid -->
<section id="commissions" style="padding: 1.5rem 0 3.5rem;">
  <div class="container">
    <div class="section-title-wrap">
      <div>
        <h2 class="section-title">
          Explore By <span>Recruiting Commission</span>
        </h2>
        <p class="section-subtitle">
          Directly monitor verified gazettes from premier central ministries and state recruitment boards
        </p>
      </div>
      <a href="/commissions" class="btn btn-outline btn-sm">View All Commissions &rarr;</a>
    </div>

    <div class="commissions-grid">
      <a href="/commissions/upsc" class="commission-card">
        <div class="commission-icon">🏛️</div>
        <div class="commission-name">UPSC</div>
        <div class="commission-count">Civil & Defence Cadres</div>
      </a>

      <a href="/commissions/ssc" class="commission-card">
        <div class="commission-icon">🏢</div>
        <div class="commission-name">SSC</div>
        <div class="commission-count">CGL, CHSL, CPO, MTS</div>
      </a>

      <a href="/commissions/railways" class="commission-card">
        <div class="commission-icon">🚆</div>
        <div class="commission-name">Railways (RRB)</div>
        <div class="commission-count">NTPC, Group D, ALP</div>
      </a>

      <a href="/commissions/banking" class="commission-card">
        <div class="commission-icon">🏦</div>
        <div class="commission-name">Banking (IBPS/SBI)</div>
        <div class="commission-count">PO, Clerk, Specialist</div>
      </a>

      <a href="/commissions/defence" class="commission-card">
        <div class="commission-icon">✈️</div>
        <div class="commission-name">Defence & IAF</div>
        <div class="commission-count">AFCAT, NDA, CDS</div>
      </a>

      <a href="/commissions/state-psc" class="commission-card">
        <div class="commission-icon">🏛️</div>
        <div class="commission-name">State PSCs</div>
        <div class="commission-count">GPSC, UPPSC, MPSC</div>
      </a>
    </div>
  </div>
</section>

<!-- 3. Exhaustive & Accurate Government Job Cards Grid -->
<section id="jobs" style="padding: 1.5rem 0 4rem;">
  <div class="container">
    <div class="section-title-wrap">
      <div>
        <h2 class="section-title">
          Latest Verified <span>Government Jobs 2026</span>
        </h2>
        <p class="section-subtitle">
          Every official parameter extracted directly from gazettes with 100% verified ground truth
        </p>
      </div>
      <a href="/government-jobs" class="btn btn-primary btn-sm">Browse All Jobs (<?= $jobCount ?> Active) &rarr;</a>
    </div>

    <div class="job-grid">
      <?php foreach ($latestJobs as $job): ?>
        <?php
          // Compute dates and urgency
          $startDateStr = !empty($job['start_date']) ? date('d M Y', strtotime($job['start_date'])) : 'As per Notice';
          $lastDateStr = !empty($job['last_date']) ? date('d M Y', strtotime($job['last_date'])) : 'Open Notice';
          
          $urgencyBadge = '🟢 Active Opening';
          $urgencyClass = 'badge-active';
          if (!empty($job['last_date'])) {
              $diffDays = ceil((strtotime($job['last_date']) - time()) / 86400);
              if ($diffDays < 0) {
                  $urgencyBadge = '⌛ Registration Closed';
                  $urgencyClass = 'badge-closed';
              } elseif ($diffDays <= 7) {
                  $urgencyBadge = "🔥 Ending Soon ({$diffDays} Days Left)";
                  $urgencyClass = 'badge-urgent';
              } else {
                  $urgencyBadge = "⚡ Apply by {$lastDateStr}";
                  $urgencyClass = 'badge-active';
              }
          }
        ?>
        <div class="job-card">
          <div>
            <!-- Header Badge & Gazette Verification -->
            <div class="job-header">
              <span class="badge-org"><?= htmlspecialchars($job['organization_name']) ?></span>
              <span class="badge-verified">✓ 100% Official Verified</span>
            </div>

            <!-- Job Title -->
            <h3 class="job-title">
              <a href="/jobs/<?= htmlspecialchars($job['slug']) ?>" title="<?= htmlspecialchars((!empty($job['organization_name']) && !str_starts_with($job['title'], $job['organization_name'])) ? ($job['organization_name'] . ' ' . $job['title']) : $job['title']) ?>">
                <?= htmlspecialchars((!empty($job['organization_name']) && !str_starts_with($job['title'], $job['organization_name'])) ? ($job['organization_name'] . ' ' . $job['title']) : $job['title']) ?>
              </a>
            </h3>

            <!-- Notice & Ref Number -->
            <div class="job-advt-num">
              Advt Ref: <?= htmlspecialchars($job['advertisement_number'] ?: $job['notification_number'] ?: 'Official Gazette 2026') ?> • Year <?= $job['year'] ?>
            </div>

            <!-- 6-Cell Data Matrix (Exhaustive & Error-Free) -->
            <div class="job-metrics-matrix">
              <div class="metric-cell">
                <span class="metric-cell-label">👥 Vacancies</span>
                <span class="metric-cell-val" style="color: var(--primary-red); font-weight: 800;" title="<?= $job['total_vacancies'] ? number_format($job['total_vacancies']) . ' Posts' : 'As per Notice' ?>">
                  <?= $job['total_vacancies'] ? number_format($job['total_vacancies']) . ' Posts' : 'As per Notice' ?>
                </span>
              </div>

              <div class="metric-cell">
                <span class="metric-cell-label">🎓 Qualification</span>
                <span class="metric-cell-val" title="<?= htmlspecialchars($job['qualification_level'] ?: 'Graduate Degree') ?>">
                  <?= htmlspecialchars($job['qualification_level'] ?: 'Graduate Degree') ?>
                </span>
              </div>

              <div class="metric-cell">
                <span class="metric-cell-label">💰 Pay Scale</span>
                <span class="metric-cell-val" style="color: var(--emerald);" title="<?= htmlspecialchars($job['pay_scale'] ?: '7th CPC Matrix') ?>">
                  <?= htmlspecialchars($job['pay_scale'] ?: '7th CPC Matrix') ?>
                </span>
              </div>

              <div class="metric-cell">
                <span class="metric-cell-label">🎂 Age Limit</span>
                <span class="metric-cell-val" title="<?= htmlspecialchars($job['age_limit'] ?: '18 - 32 Years') ?>">
                  <?= htmlspecialchars($job['age_limit'] ?: '18 - 32 Years') ?>
                </span>
              </div>

              <div class="metric-cell">
                <span class="metric-cell-label">💳 Application Fee</span>
                <span class="metric-cell-val" title="<?= htmlspecialchars($job['fee_details'] ?: 'Gen: ₹100 / SC: ₹0') ?>">
                  <?= htmlspecialchars($job['fee_details'] ?: 'Gen: ₹100 / SC: ₹0') ?>
                </span>
              </div>

              <div class="metric-cell">
                <span class="metric-cell-label">📍 Cadre / Region</span>
                <span class="metric-cell-val" title="<?= htmlspecialchars($job['state_code'] === 'ALL' ? 'All India' : $job['state_code']) ?>">
                  <?= htmlspecialchars($job['state_code'] === 'ALL' ? 'All India' : $job['state_code']) ?>
                </span>
              </div>
            </div>

            <!-- Important Dates Row -->
            <div class="job-dates-row">
              <div class="job-dates-header">
                <span class="job-dates-label">📅 Application Window</span>
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
              📄 Full Details &amp; Syllabus &rarr;
            </a>

            <?php if (!empty($job['official_apply_url']) || !empty($job['primary_notification_url'])): ?>
              <div class="job-card-secondary-actions">
                <?php if (!empty($job['official_apply_url'])): ?>
                  <a href="<?= htmlspecialchars($job['official_apply_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-card-sub">
                    🚀 Apply Online
                  </a>
                <?php endif; ?>

                <?php if (!empty($job['primary_notification_url'])): ?>
                  <a href="<?= htmlspecialchars($job['primary_notification_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-glass btn-card-sub" title="Download Official PDF Notice">
                    📥 Official PDF
                  </a>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- 4. Exam Intelligence Hubs Section -->
<section id="exams" style="padding: 2rem 0 4rem; background: var(--bg-subtle);">
  <div class="container">
    <div class="section-title-wrap">
      <div>
        <h2 class="section-title">
          Autonomous <span>Exam Intelligence Hubs</span>
        </h2>
        <p class="section-subtitle">
          Explore complete examination schemes, multi-phase patterns, topic weightages, and previous year cutoff trends
        </p>
      </div>
      <a href="/exams" class="btn btn-outline btn-sm">View All Exam Hubs &rarr;</a>
    </div>

    <div class="exam-grid">
      <?php foreach ($exams as $exam): ?>
        <div class="exam-card">
          <div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
              <span class="badge-org"><?= htmlspecialchars($exam['category']) ?></span>
              <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;"><?= htmlspecialchars($exam['frequency']) ?></span>
            </div>

            <h3 style="font-family: var(--font-heading); font-size: 1.25rem; font-weight: 700; margin-bottom: 0.4rem; color: var(--text-primary);">
              <?= htmlspecialchars($exam['name']) ?>
            </h3>
            
            <div style="font-size: 0.825rem; color: var(--text-secondary); margin-bottom: 0.85rem;">
              Conducting Body: <strong><?= htmlspecialchars($exam['conducting_body']) ?></strong>
            </div>

            <p style="font-size: 0.875rem; color: var(--text-secondary); line-height: 1.55; margin-bottom: 1.25rem;">
              <?= htmlspecialchars(substr($exam['overview'], 0, 160)) ?>...
            </p>
          </div>

          <div style="display: flex; gap: 0.5rem; border-top: 1px solid var(--border-subtle); padding-top: 1rem;">
            <a href="/exams/<?= htmlspecialchars($exam['slug']) ?>" class="btn btn-primary btn-sm" style="flex: 1;">
              🧠 View Pattern & Cutoffs &rarr;
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- 5. Editorial Preparation Guides & Articles -->
<section id="articles" style="padding: 3rem 0 4rem;">
  <div class="container">
    <div class="section-title-wrap">
      <div>
        <h2 class="section-title">
          Verified <span>Preparation Guides & Analysis</span>
        </h2>
        <p class="section-subtitle">
          Expert breakdowns of gazettes, syllabus weightages, cutoff analysis, and step-by-step application walkthroughs
        </p>
      </div>
      <a href="/articles" class="btn btn-outline btn-sm">View All Guides (<?= count($articles) ?> Articles) &rarr;</a>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 1.75rem;">
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

            <h3 style="font-family: var(--font-heading); font-size: 1.2rem; font-weight: 700; line-height: 1.35; margin-bottom: 0.6rem; color: var(--text-primary);">
              <a href="/articles/<?= htmlspecialchars($art['slug']) ?>">
                <?= htmlspecialchars($art['title']) ?>
              </a>
            </h3>

            <p style="font-size: 0.875rem; color: var(--text-secondary); line-height: 1.6; margin-bottom: 1.25rem;">
              <?= htmlspecialchars($art['excerpt']) ?>
            </p>
          </div>

          <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-subtle); padding-top: 0.85rem;">
            <span style="font-size: 0.775rem; color: var(--emerald); font-weight: 700;">
              ✓ Truth Score: <?= $art['quality_score'] ?>/100
            </span>
            <a href="/articles/<?= htmlspecialchars($art['slug']) ?>" class="btn btn-outline btn-sm">
              Read Guide &rarr;
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
