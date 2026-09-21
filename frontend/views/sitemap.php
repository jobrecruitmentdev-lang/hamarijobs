<?php
require_once __DIR__ . '/../../backend/app/Database.php';
require_once __DIR__ . '/partials/icons.php';
use App\Database;

$db = Database::getConnection();

// 1. Fetch All Active / Process Recruitments
$jobsStmt = $db->query("
    SELECT id, title, slug, organization_name, total_vacancies, state_code, qualification_level, updated_at, status 
    FROM recruitments 
    WHERE status != 'Archived' 
    ORDER BY updated_at DESC
");
$allJobs = $jobsStmt->fetchAll();

// 2. Fetch All Active Exam Hubs
$examsStmt = $db->query("
    SELECT id, name, short_name, slug, category, conducting_body 
    FROM exams 
    WHERE is_active = 1 
    ORDER BY category ASC, name ASC
");
$allExams = $examsStmt->fetchAll();

// 3. Fetch All Active Commissions
$commStmt = $db->query("
    SELECT id, name, short_name, slug, category 
    FROM commissions 
    WHERE is_active = 1 
    ORDER BY name ASC
");
$allCommissions = $commStmt->fetchAll();

// 4. Fetch All Published Preparation Articles & Guides
$artStmt = $db->query("
    SELECT id, title, slug, published_at, category 
    FROM articles 
    WHERE status = 'Published' 
    ORDER BY published_at DESC
");
$allArticles = $artStmt->fetchAll();

// SEO Meta Configuration
$pageTitle = "Master Directory & Complete Crawl Index — HamariJobs";
$pageDesc = "Complete directory index of all verified official government recruitment notifications, examinations, syllabus patterns, cutoff archives, and recruiting commissions across India.";
$canonicalUrl = "https://hamarijobs.com/sitemap";

$seo = [
    'title' => $pageTitle,
    'description' => $pageDesc,
    'canonical' => $canonicalUrl,
    'og_type' => 'website',
    'og_image' => 'https://hamarijobs.com/assets/images/logo.png',
    'twitter_card' => 'summary_large_image',
    'schemas' => [
        [
            "@context" => "https://schema.org",
            "@type" => "CollectionPage",
            "name" => $pageTitle,
            "description" => $pageDesc,
            "url" => $canonicalUrl,
            "isPartOf" => [
                "@type" => "WebSite",
                "name" => "HamariJobs",
                "url" => "https://hamarijobs.com"
            ]
        ],
        [
            "@context" => "https://schema.org",
            "@type" => "BreadcrumbList",
            "itemListElement" => [
                [
                    "@type" => "ListItem",
                    "position" => 1,
                    "name" => "Home",
                    "item" => "https://hamarijobs.com"
                ],
                [
                    "@type" => "ListItem",
                    "position" => 2,
                    "name" => "Master Sitemap",
                    "item" => $canonicalUrl
                ]
            ]
        ]
    ]
];

require_once __DIR__ . '/partials/header.php';
?>

<div class="container" style="padding: 2.5rem 0 5rem;">
  
  <!-- Breadcrumb -->
  <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
    <a href="/" style="color: var(--text-secondary); text-decoration: none;">Home</a> 
    <span>&rsaquo;</span>
    <span style="color: var(--primary-red); font-weight: 600;">Master Directory Sitemap</span>
  </div>

  <!-- Hero Header -->
  <div style="background: linear-gradient(135deg, #ffffff 0%, #fff1f2 100%); border: 1px solid #fecdd3; border-radius: 12px; padding: 2.25rem 2rem; margin-bottom: 2.5rem; box-shadow: 0 4px 15px rgba(220, 38, 38, 0.04);">
    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
      <span style="background: #fee2e2; color: var(--primary-red); font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; padding: 0.25rem 0.6rem; border-radius: 4px;">
        INDEX ARCHIVE
      </span>
      <span style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">Updated Daily for Search Engines & Aspirants</span>
    </div>
    <h1 style="font-family: var(--font-heading); font-size: 2.25rem; font-weight: 900; color: #0f172a; margin-bottom: 0.5rem; letter-spacing: -0.02em;">
      HamariJobs Master Gazette Directory & Sitemap
    </h1>
    <p style="font-size: 1.05rem; color: #475569; max-width: 820px; line-height: 1.6; margin: 0;">
      A single unified crawl map providing instant 1-click access to all active government notifications, examination intelligence dossiers, syllabus guides, and recruiting boards across India.
    </p>

    <!-- Quick Jump Links -->
    <div style="display: flex; flex-wrap: wrap; gap: 0.65rem; margin-top: 1.5rem;">
      <a href="#jobs-section" class="btn btn-sm btn-outline" style="background: #ffffff;">📋 Active Jobs (<?= count($allJobs) ?>)</a>
      <a href="#exams-section" class="btn btn-sm btn-outline" style="background: #ffffff;">🎯 Exam Hubs (<?= count($allExams) ?>)</a>
      <a href="#commissions-section" class="btn btn-sm btn-outline" style="background: #ffffff;">🏛️ Commissions (<?= count($allCommissions) ?>)</a>
      <a href="#articles-section" class="btn btn-sm btn-outline" style="background: #ffffff;">📚 Preparation Guides (<?= count($allArticles) ?>)</a>
      <a href="#silos-section" class="btn btn-sm btn-outline" style="background: #ffffff;">📍 Qualification & State Hubs</a>
    </div>
  </div>

  <!-- SECTION 1: PROGRAMMATIC SILOS & CATEGORY HUBS -->
  <section id="silos-section" style="margin-bottom: 3.5rem;">
    <div style="border-bottom: 2px solid #fee2e2; padding-bottom: 0.65rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: flex-end;">
      <div>
        <h2 style="font-size: 1.45rem; font-weight: 800; color: #0f172a; margin: 0;">📍 Programmatic Hubs & Targeted Keyword Silos</h2>
        <p style="font-size: 0.875rem; color: #64748b; margin: 0.25rem 0 0;">Curated high-intent directory pages filtered by qualification, sector, and jurisdiction</p>
      </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem;">
      
      <!-- By Qualification -->
      <div style="background: #ffffff; border: 1px solid var(--border-subtle); border-radius: 8px; padding: 1.25rem;">
        <h3 style="font-size: 1rem; font-weight: 800; color: var(--primary-red); margin-bottom: 0.75rem;">🎓 By Qualification</h3>
        <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.9rem;">
          <li><a href="/jobs/10th-pass" style="color: #0284c7; text-decoration: none; font-weight: 600;">→ 10th Pass Government Jobs 2026</a></li>
          <li><a href="/jobs/12th-pass" style="color: #0284c7; text-decoration: none; font-weight: 600;">→ 12th Pass Government Jobs 2026</a></li>
          <li><a href="/jobs/graduate" style="color: #0284c7; text-decoration: none; font-weight: 600;">→ Graduate Level Government Jobs 2026</a></li>
        </ul>
      </div>

      <!-- By Department -->
      <div style="background: #ffffff; border: 1px solid var(--border-subtle); border-radius: 8px; padding: 1.25rem;">
        <h3 style="font-size: 1rem; font-weight: 800; color: var(--primary-red); margin-bottom: 0.75rem;">🚂 By Department & Sector</h3>
        <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.9rem;">
          <li><a href="/jobs/railway" style="color: #0284c7; text-decoration: none; font-weight: 600;">→ Indian Railway Recruitment 2026</a></li>
          <li><a href="/jobs/police" style="color: #0284c7; text-decoration: none; font-weight: 600;">→ Police & Paramilitary Constable / SI Jobs</a></li>
          <li><a href="/jobs/bank" style="color: #0284c7; text-decoration: none; font-weight: 600;">→ Public Sector Banking & IBPS / SBI Jobs</a></li>
          <li><a href="/jobs/defence" style="color: #0284c7; text-decoration: none; font-weight: 600;">→ Armed Forces & Defence Recruitment</a></li>
        </ul>
      </div>

      <!-- By State -->
      <div style="background: #ffffff; border: 1px solid var(--border-subtle); border-radius: 8px; padding: 1.25rem;">
        <h3 style="font-size: 1rem; font-weight: 800; color: var(--primary-red); margin-bottom: 0.75rem;">🗺️ By State Jurisdiction</h3>
        <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.9rem;">
          <li><a href="/jobs/uttar-pradesh" style="color: #0284c7; text-decoration: none; font-weight: 600;">→ Uttar Pradesh (UP) Government Jobs</a></li>
          <li><a href="/jobs/bihar" style="color: #0284c7; text-decoration: none; font-weight: 600;">→ Bihar (BPSC / Police) Jobs</a></li>
          <li><a href="/jobs/rajasthan" style="color: #0284c7; text-decoration: none; font-weight: 600;">→ Rajasthan (RPSC / RSMSSB) Jobs</a></li>
          <li><a href="/jobs/delhi" style="color: #0284c7; text-decoration: none; font-weight: 600;">→ Delhi NCR & DSSSB Recruitments</a></li>
          <li><a href="/jobs/madhya-pradesh" style="color: #0284c7; text-decoration: none; font-weight: 600;">→ Madhya Pradesh (MPESB / MPPSC) Jobs</a></li>
        </ul>
      </div>

      <!-- Specialized Directories -->
      <div style="background: #ffffff; border: 1px solid var(--border-subtle); border-radius: 8px; padding: 1.25rem;">
        <h3 style="font-size: 1rem; font-weight: 800; color: var(--primary-red); margin-bottom: 0.75rem;">⚡ Core Directories</h3>
        <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.9rem;">
          <li><a href="/government-jobs" style="color: #0284c7; text-decoration: none; font-weight: 600;">→ All Active Government Jobs Directory</a></li>
          <li><a href="/admit-cards" style="color: #0284c7; text-decoration: none; font-weight: 600;">→ Examination Admit Cards & Hall Tickets</a></li>
          <li><a href="/results" style="color: #0284c7; text-decoration: none; font-weight: 600;">→ Examination Results & Scorecard Merit Lists</a></li>
          <li><a href="/commissions" style="color: #0284c7; text-decoration: none; font-weight: 600;">→ Official Recruiting Commissions Directory</a></li>
        </ul>
      </div>

    </div>
  </section>

  <!-- SECTION 2: ALL ACTIVE RECRUITMENTS -->
  <section id="jobs-section" style="margin-bottom: 3.5rem;">
    <div style="border-bottom: 2px solid #fee2e2; padding-bottom: 0.65rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: flex-end;">
      <div>
        <h2 style="font-size: 1.45rem; font-weight: 800; color: #0f172a; margin: 0;">📋 Verified Government Recruitment Notifications (<?= count($allJobs) ?>)</h2>
        <p style="font-size: 0.875rem; color: #64748b; margin: 0.25rem 0 0;">Official notices extracted directly from Central & State Gazettes</p>
      </div>
      <a href="/government-jobs" class="btn btn-sm btn-outline">View Directory &rarr;</a>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1rem;">
      <?php foreach ($allJobs as $j): ?>
        <div style="background: #ffffff; border: 1px solid var(--border-subtle); border-radius: 8px; padding: 1rem 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
          <div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
              <span style="font-size: 0.75rem; font-weight: 800; color: var(--primary-red); background: #fee2e2; padding: 0.15rem 0.5rem; border-radius: 3px;">
                <?= htmlspecialchars($j['organization_name']) ?>
              </span>
              <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;">
                <?= $j['total_vacancies'] ? number_format($j['total_vacancies']) . ' Posts' : 'Various' ?>
              </span>
            </div>
            <h3 style="font-size: 0.95rem; font-weight: 700; line-height: 1.45; margin: 0 0 0.5rem;">
              <a href="/jobs/<?= htmlspecialchars($j['slug']) ?>" style="color: #0f172a; text-decoration: none;" title="<?= htmlspecialchars($j['title']) ?>">
                <?= htmlspecialchars($j['title']) ?>
              </a>
            </h3>
          </div>
          <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f1f5f9; padding-top: 0.5rem; margin-top: 0.5rem; font-size: 0.8rem; color: var(--text-muted);">
            <span><?= htmlspecialchars($j['qualification_level'] ?: 'Graduate / 12th') ?></span>
            <a href="/jobs/<?= htmlspecialchars($j['slug']) ?>" style="color: var(--primary-red); font-weight: 700; text-decoration: none;">View Notice &rarr;</a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- SECTION 3: EXAMINATION INTELLIGENCE HUBS -->
  <section id="exams-section" style="margin-bottom: 3.5rem;">
    <div style="border-bottom: 2px solid #fee2e2; padding-bottom: 0.65rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: flex-end;">
      <div>
        <h2 style="font-size: 1.45rem; font-weight: 800; color: #0f172a; margin: 0;">🎯 Master Examination Intelligence Hubs (<?= count($allExams) ?>)</h2>
        <p style="font-size: 0.875rem; color: #64748b; margin: 0.25rem 0 0;">Complete syllabus, exam patterns, marks weightage, and cutoff analysis</p>
      </div>
      <a href="/exams" class="btn btn-sm btn-outline">Explore All Exams &rarr;</a>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(310px, 1fr)); gap: 1.25rem;">
      <?php foreach ($allExams as $ex): ?>
        <div style="background: #ffffff; border: 1px solid var(--border-subtle); border-radius: 8px; padding: 1.25rem;">
          <div style="font-size: 0.75rem; font-weight: 700; color: #0284c7; margin-bottom: 0.35rem;">
            <?= htmlspecialchars($ex['category']) ?> • <?= htmlspecialchars($ex['conducting_body']) ?>
          </div>
          <h3 style="font-size: 1.05rem; font-weight: 800; margin: 0 0 0.5rem;">
            <a href="/exams/<?= htmlspecialchars($ex['slug']) ?>" style="color: #0f172a; text-decoration: none;">
              <?= htmlspecialchars($ex['name']) ?>
            </a>
          </h3>
          <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.75rem; font-size: 0.8rem;">
            <a href="/exams/<?= htmlspecialchars($ex['slug']) ?>/syllabus" style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 0.2rem 0.5rem; border-radius: 4px; text-decoration: none; color: #334155;">📖 Syllabus</a>
            <a href="/exams/<?= htmlspecialchars($ex['slug']) ?>/exam-pattern" style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 0.2rem 0.5rem; border-radius: 4px; text-decoration: none; color: #334155;">📝 Pattern</a>
            <a href="/exams/<?= htmlspecialchars($ex['slug']) ?>/cutoff" style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 0.2rem 0.5rem; border-radius: 4px; text-decoration: none; color: #334155;">📊 Cutoffs</a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- SECTION 4: RECRUITING COMMISSIONS -->
  <section id="commissions-section" style="margin-bottom: 3.5rem;">
    <div style="border-bottom: 2px solid #fee2e2; padding-bottom: 0.65rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: flex-end;">
      <div>
        <h2 style="font-size: 1.45rem; font-weight: 800; color: #0f172a; margin: 0;">🏛️ Official Recruiting Commissions & Boards (<?= count($allCommissions) ?>)</h2>
        <p style="font-size: 0.875rem; color: #64748b; margin: 0.25rem 0 0;">Dedicated dossiers for Central & State recruiting authorities</p>
      </div>
      <a href="/commissions" class="btn btn-sm btn-outline">All Commissions &rarr;</a>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 1rem;">
      <?php foreach ($allCommissions as $c): ?>
        <div style="background: #ffffff; border: 1px solid var(--border-subtle); border-radius: 8px; padding: 1rem 1.25rem;">
          <h3 style="font-size: 0.95rem; font-weight: 700; margin: 0 0 0.35rem;">
            <a href="/commissions/<?= htmlspecialchars($c['slug']) ?>" style="color: #0f172a; text-decoration: none;">
              <?= htmlspecialchars($c['name']) ?>
            </a>
          </h3>
          <span style="font-size: 0.78rem; color: var(--text-muted);"><?= htmlspecialchars($c['category']) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- SECTION 5: PREPARATION GUIDES & ARTICLES -->
  <section id="articles-section" style="margin-bottom: 3.5rem;">
    <div style="border-bottom: 2px solid #fee2e2; padding-bottom: 0.65rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: flex-end;">
      <div>
        <h2 style="font-size: 1.45rem; font-weight: 800; color: #0f172a; margin: 0;">📚 Authoritative Preparation Guides & Editorial Dossiers (<?= count($allArticles) ?>)</h2>
        <p style="font-size: 0.875rem; color: #64748b; margin: 0.25rem 0 0;">Deep-dive preparation strategies, eligibility breakdowns, and official guidelines</p>
      </div>
      <a href="/articles" class="btn btn-sm btn-outline">All Articles &rarr;</a>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1rem;">
      <?php foreach ($allArticles as $art): ?>
        <div style="background: #ffffff; border: 1px solid var(--border-subtle); border-radius: 8px; padding: 1rem 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
          <h3 style="font-size: 0.92rem; font-weight: 700; line-height: 1.45; margin: 0 0 0.5rem;">
            <a href="/articles/<?= htmlspecialchars($art['slug']) ?>" style="color: #0f172a; text-decoration: none;" title="<?= htmlspecialchars($art['title']) ?>">
              <?= htmlspecialchars($art['title']) ?>
            </a>
          </h3>
          <div style="display: flex; justify-content: space-between; font-size: 0.78rem; color: var(--text-muted); border-top: 1px solid #f1f5f9; padding-top: 0.5rem; margin-top: 0.5rem;">
            <span><?= !empty($art['published_at']) ? date('d M Y', strtotime($art['published_at'])) : 'Verified Guide' ?></span>
            <a href="/articles/<?= htmlspecialchars($art['slug']) ?>" style="color: var(--primary-red); font-weight: 700; text-decoration: none;">Read Guide &rarr;</a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
