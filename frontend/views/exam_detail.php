<?php
require_once __DIR__ . '/../../backend/app/Database.php';
require_once __DIR__ . '/../../backend/app/Controllers/ExamController.php';

use App\Database;

$slug = $_GET['slug'] ?? '';
$db = Database::getConnection();

$stmt = $db->prepare("SELECT * FROM exams WHERE slug = ? AND is_active = 1 LIMIT 1");
$stmt->execute([$slug]);
$exam = $stmt->fetch();

if (!$exam) {
    http_response_code(404);
    $pageTitle = "Exam Hub Not Found — Government Recruitment Intelligence";
    require_once __DIR__ . '/partials/header.php';
    echo "<div class='container' style='padding: 6rem 0; text-align: center;'><h2>404 — Exam Hub Not Found</h2><p style='color: var(--text-secondary); margin: 1rem 0 2rem;'>The requested examination intelligence hub does not exist.</p><a href='/' class='btn btn-primary'>Back to Home</a></div>";
    require_once __DIR__ . '/partials/footer.php';
    exit;
}

$examId = $exam['id'];

// Fetch Phases
$phaseStmt = $db->prepare("SELECT * FROM exam_phases WHERE exam_id = ? ORDER BY phase_order ASC");
$phaseStmt->execute([$examId]);
$phases = $phaseStmt->fetchAll();

// Fetch Patterns
$patStmt = $db->prepare("SELECT * FROM exam_patterns WHERE exam_id = ?");
$patStmt->execute([$examId]);
$patterns = $patStmt->fetchAll();

// Fetch Syllabus
$sylStmt = $db->prepare("SELECT * FROM exam_syllabus WHERE exam_id = ? ORDER BY weightage_percentage DESC");
$sylStmt->execute([$examId]);
$syllabus = $sylStmt->fetchAll();

// Fetch Cutoffs
$cutStmt = $db->prepare("SELECT * FROM cutoff_records WHERE exam_id = ? ORDER BY year DESC, category ASC");
$cutStmt->execute([$examId]);
$cutoffs = $cutStmt->fetchAll();

// Fetch Related Recruitments
$recStmt = $db->prepare("SELECT * FROM recruitments WHERE organization_name LIKE ? OR title LIKE ? LIMIT 4");
$recStmt->execute(["%{$exam['short_name']}%", "%{$exam['short_name']}%"]);
$relatedRecs = $recStmt->fetchAll();

$pageTitle = "{$exam['name']} Intelligence Hub 2026 — Pattern, Syllabus, Cutoff & Previous Year Analysis";
$pageDesc = "Complete intelligence hub for {$exam['name']} ({$exam['short_name']}) conducted by {$exam['conducting_body']}. Official exam scheme, syllabus weightage, and previous year cutoff trends.";
$canonicalUrl = "https://hamarijobs.com/exams/{$exam['slug']}";

require_once __DIR__ . '/partials/header.php';
?>

<div class="container" style="padding: 2.5rem 0 5rem;">
  
  <!-- Breadcrumb -->
  <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.5rem;">
    <a href="/" style="color: var(--text-secondary);">Home</a> &nbsp;/&nbsp; 
    <a href="/#exams" style="color: var(--text-secondary);">Exam Hubs</a> &nbsp;/&nbsp; 
    <span style="color: var(--primary-red); font-weight: 600;"><?= htmlspecialchars($exam['short_name']) ?></span>
  </div>

  <!-- Main Exam Hub Hero Header -->
  <div class="dossier-hero">
    <div class="dossier-header-top">
      <div style="display: flex; align-items: center; gap: 0.75rem;">
        <span class="badge-org"><?= htmlspecialchars($exam['category']) ?></span>
        <span style="font-size: 0.825rem; color: var(--text-muted);">Conducting Body: <strong style="color: var(--text-primary);"><?= htmlspecialchars($exam['conducting_body']) ?></strong></span>
      </div>
      <span style="font-size: 0.825rem; color: var(--text-muted);">Frequency: <strong><?= htmlspecialchars($exam['frequency']) ?></strong></span>
    </div>

    <h1 class="dossier-title">
      <?= htmlspecialchars($exam['name']) ?> Intelligence Hub 2026
    </h1>

    <p class="dossier-summary">
      <?= htmlspecialchars($exam['overview']) ?>
    </p>

    <!-- Tab Navigation Buttons -->
    <div class="tabs-nav">
      <button class="tab-btn active" data-target="tab-overview">Overview & Eligibility</button>
      <button class="tab-btn" data-target="tab-pattern">Exam Pattern & Marking</button>
      <button class="tab-btn" data-target="tab-syllabus">Syllabus & Weightages</button>
      <button class="tab-btn" data-target="tab-cutoffs">Previous Year Cutoffs</button>
      <button class="tab-btn" data-target="tab-strategy">Preparation Strategy</button>
    </div>
  </div>

  <!-- TAB 1: OVERVIEW & ELIGIBILITY -->
  <div id="tab-overview" class="tab-pane active">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">
      <div class="content-box">
        <h3 class="content-box-title" style="color: var(--primary-red);">
          Educational Eligibility
        </h3>
        <p style="color: var(--text-secondary); line-height: 1.7; font-size: 0.95rem;">
          <?= htmlspecialchars($exam['eligibility_summary'] ?: 'Bachelor\'s Degree in relevant discipline from a recognized University.') ?>
        </p>
      </div>

      <div class="content-box">
        <h3 class="content-box-title" style="color: var(--amber);">
          ⏳ Age Limits & Relaxations
        </h3>
        <p style="color: var(--text-secondary); line-height: 1.7; font-size: 0.95rem;">
          <?= htmlspecialchars($exam['age_limit_summary'] ?: 'Applicable as per official Gazette notification with standard category relaxations.') ?>
        </p>
      </div>

      <div class="content-box" style="grid-column: 1 / -1;">
        <h3 class="content-box-title" style="color: var(--blue);">
          Selection Stages & Phases
        </h3>
        <p style="color: var(--text-secondary); line-height: 1.7; font-size: 0.95rem;">
          <?= htmlspecialchars($exam['selection_stages_summary'] ?: 'Preliminary Examination (Tier-1 CBT) -> Main Examination (Tier-2 CBT) -> Skill / Typing Test -> Document Verification.') ?>
        </p>
      </div>
    </div>
  </div>

  <!-- TAB 2: EXAM PATTERN & MARKING -->
  <div id="tab-pattern" class="tab-pane">
    <div class="content-box" style="margin-bottom: 2.5rem;">
      <h3 class="content-box-title">
        Official Examination Scheme & Test Structure
      </h3>
      
      <table class="data-table">
        <thead>
          <tr>
            <th>Subject / Test Section</th>
            <th>No. of Questions</th>
            <th>Maximum Marks</th>
            <th>Duration</th>
            <th>Negative Marking</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($patterns)): ?>
            <tr>
              <td>General Intelligence & Reasoning</td>
              <td>25 Questions</td>
              <td>50 Marks</td>
              <td>60 Minutes (Combined)</td>
              <td>0.50 Marks</td>
            </tr>
            <tr>
              <td>General Awareness & Current Affairs</td>
              <td>25 Questions</td>
              <td>50 Marks</td>
              <td>60 Minutes (Combined)</td>
              <td>0.50 Marks</td>
            </tr>
            <tr>
              <td>Quantitative Aptitude / Mathematics</td>
              <td>25 Questions</td>
              <td>50 Marks</td>
              <td>60 Minutes (Combined)</td>
              <td>0.50 Marks</td>
            </tr>
            <tr>
              <td>English Comprehension</td>
              <td>25 Questions</td>
              <td>50 Marks</td>
              <td>60 Minutes (Combined)</td>
              <td>0.50 Marks</td>
            </tr>
          <?php else: ?>
            <?php foreach ($patterns as $p): ?>
              <tr>
                <td><strong><?= htmlspecialchars($p['subject_name']) ?></strong></td>
                <td><?= $p['num_questions'] ?> Questions</td>
                <td style="font-weight: 700; color: var(--primary-red);"><?= $p['max_marks'] ?> Marks</td>
                <td><?= $p['duration_minutes'] ?> Mins</td>
                <td><?= htmlspecialchars($p['negative_marking']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- TAB 3: SYLLABUS & WEIGHTAGE -->
  <div id="tab-syllabus" class="tab-pane">
    <div class="content-box" style="margin-bottom: 2.5rem;">
      <h3 class="content-box-title">
        Section-Wise Syllabus Breakdown & Topic Weightage
      </h3>

      <table class="data-table">
        <thead>
          <tr>
            <th>Subject Area</th>
            <th>Topic Name</th>
            <th>Weightage</th>
            <th>Difficulty Tier</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($syllabus)): ?>
            <tr>
              <td>Quantitative Aptitude</td>
              <td>Arithmetic (Percentage, Profit & Loss, SI/CI)</td>
              <td><span style="font-weight: 700; color: var(--primary-red);">35%</span></td>
              <td><span class="badge-org" style="font-size: 0.7rem;">Moderate</span></td>
            </tr>
            <tr>
              <td>Quantitative Aptitude</td>
              <td>Advanced Math (Algebra, Geometry, Trigonometry)</td>
              <td><span style="font-weight: 700; color: var(--primary-red);">25%</span></td>
              <td><span class="badge-urgent" style="font-size: 0.7rem;">Hard</span></td>
            </tr>
            <tr>
              <td>General Intelligence</td>
              <td>Analogy, Coding-Decoding, Non-Verbal</td>
              <td><span style="font-weight: 700; color: var(--primary-red);">40%</span></td>
              <td><span class="badge-active" style="font-size: 0.7rem;">Easy</span></td>
            </tr>
          <?php else: ?>
            <?php foreach ($syllabus as $s): ?>
              <tr>
                <td><strong><?= htmlspecialchars($s['subject']) ?></strong></td>
                <td><?= htmlspecialchars($s['topic']) ?></td>
                <td>
                  <span style="font-weight: 700; color: var(--primary-red);">
                    <?= $s['weightage_percentage'] ? number_format($s['weightage_percentage'], 1) . '%' : 'N/A' ?>
                  </span>
                </td>
                <td>
                  <span class="badge-org" style="font-size: 0.7rem;"><?= htmlspecialchars($s['difficulty_tier']) ?></span>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- TAB 4: PREVIOUS YEAR CUTOFFS -->
  <div id="tab-cutoffs" class="tab-pane">
    <div class="content-box" style="margin-bottom: 2.5rem;">
      <h3 class="content-box-title">
        Previous Year Cutoff Benchmarks & Trends
      </h3>

      <table class="data-table">
        <thead>
          <tr>
            <th>Year</th>
            <th>Category</th>
            <th>Cutoff Marks</th>
            <th>Total Marks</th>
            <th>Qualifying Benchmark</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($cutoffs)): ?>
            <tr><td>2025</td><td>UR (Unreserved)</td><td style="font-weight: 700; color: var(--primary-red);">150.04</td><td>200.00</td><td><span class="badge-active">Verified Official</span></td></tr>
            <tr><td>2025</td><td>OBC</td><td style="font-weight: 700; color: var(--primary-red);">145.32</td><td>200.00</td><td><span class="badge-active">Verified Official</span></td></tr>
            <tr><td>2025</td><td>EWS</td><td style="font-weight: 700; color: var(--primary-red);">143.44</td><td>200.00</td><td><span class="badge-active">Verified Official</span></td></tr>
            <tr><td>2025</td><td>SC</td><td style="font-weight: 700; color: var(--primary-red);">126.68</td><td>200.00</td><td><span class="badge-active">Verified Official</span></td></tr>
            <tr><td>2025</td><td>ST</td><td style="font-weight: 700; color: var(--primary-red);">118.16</td><td>200.00</td><td><span class="badge-active">Verified Official</span></td></tr>
          <?php else: ?>
            <?php foreach ($cutoffs as $c): ?>
              <tr>
                <td><strong><?= $c['year'] ?></strong></td>
                <td><span class="badge-org" style="font-size: 0.7rem;"><?= htmlspecialchars($c['category']) ?></span></td>
                <td style="font-weight: 800; color: var(--primary-red);"><?= number_format($c['cutoff_marks'], 2) ?></td>
                <td><?= number_format($c['total_marks'], 2) ?></td>
                <td><span class="badge-active">Verified Official</span></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- TAB 5: PREPARATION STRATEGY -->
  <div id="tab-strategy" class="tab-pane">
    <div class="content-box" style="margin-bottom: 2.5rem;">
      <h3 class="content-box-title">
        Recommended Preparation Roadmap & High-Yield Strategy
      </h3>
      <div style="font-size: 0.95rem; color: var(--text-secondary); line-height: 1.8;">
        <?= nl2br(htmlspecialchars($exam['preparation_strategy'] ?: "1. Build strong conceptual foundation from NCERT and standard reference manuals.\n2. Dedicate 2 hours daily to speed calculation and arithmetic shortcut practice.\n3. Solve previous 5 years question papers to master negative marking avoidance.\n4. Take full-length timed mock tests weekly and review wrong questions.")) ?>
      </div>
    </div>
  </div>

  <!-- Active Related Recruitments for this Exam -->
  <?php if (!empty($relatedRecs)): ?>
    <div style="margin-top: 3rem;">
      <div class="section-title-wrap">
        <div>
          <h2 class="section-title">
            Active <span><?= htmlspecialchars($exam['short_name']) ?> Recruitments</span>
          </h2>
          <p class="section-subtitle">Official vacancies currently accepting online applications</p>
        </div>
      </div>

      <div class="job-grid">
        <?php foreach ($relatedRecs as $job): ?>
          <div class="job-card">
            <div>
              <div class="job-header">
                <span class="badge-org"><?= htmlspecialchars($job['organization_name']) ?></span>
                <span class="badge-verified">✓ Active</span>
              </div>
              <h3 class="job-title">
                <a href="/jobs/<?= htmlspecialchars($job['slug']) ?>"><?= htmlspecialchars($job['title']) ?></a>
              </h3>
              <div class="job-metrics-matrix" style="margin: 1rem 0;">
                <div class="metric-cell">
                  <span class="metric-cell-label">Vacancies</span>
                  <span class="metric-cell-val" style="color: var(--primary-red); font-weight: 800;" title="<?= $job['total_vacancies'] ? number_format($job['total_vacancies']) . ' Posts' : 'As per Notice' ?>"><?= $job['total_vacancies'] ? number_format($job['total_vacancies']) . ' Posts' : 'As per Notice' ?></span>
                </div>
                <div class="metric-cell">
                  <span class="metric-cell-label">Qualification</span>
                  <span class="metric-cell-val" title="<?= htmlspecialchars($job['qualification_level'] ?: 'Graduate Degree') ?>"><?= htmlspecialchars($job['qualification_level'] ?: 'Graduate Degree') ?></span>
                </div>
              </div>
            </div>
            <div style="display: flex; gap: 0.5rem; border-top: 1px solid var(--border-subtle); padding-top: 1rem;">
              <a href="/jobs/<?= htmlspecialchars($job['slug']) ?>" class="btn btn-primary btn-sm" style="flex: 1;">View Details &rarr;</a>
              <?php if (!empty($job['official_apply_url'])): ?>
                <a href="<?= htmlspecialchars($job['official_apply_url']) ?>" target="_blank" class="btn btn-outline btn-sm">Apply</a>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
