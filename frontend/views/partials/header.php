<?php
require_once __DIR__ . '/icons.php';
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$pageTitle = $pageTitle ?? "Government Recruitment Intelligence Platform — Verified Official Jobs, Exams & Results";
$pageDesc = $pageDesc ?? "India's premier official government recruitment intelligence portal. Autonomous discovery, verified notifications, exam patterns, syllabus, and previous year cutoff trends.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <meta name="description" content="<?= htmlspecialchars($pageDesc) ?>">
  <link rel="stylesheet" href="/assets/css/main.css?v=<?= file_exists(__DIR__ . '/../../public/css/main.css') ? filemtime(__DIR__ . '/../../public/css/main.css') : '2.1' ?>">
  <link rel="icon" type="image/png" href="/assets/images/logo.png">
  <?php if (isset($canonicalUrl)): ?>
    <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl) ?>">
  <?php endif; ?>
  <!-- Chart.js for analytics -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body>

  <!-- 1. Breaking Gazettes Live Ticker (Crimson Red) -->
  <div class="breaking-ticker">
    <div class="container ticker-wrap">
      <span class="ticker-label">
        <span class="pulse-dot"></span>
        LIVE GAZETTES
      </span>
      <div class="ticker-marquee">
        <div class="ticker-content">
          <span class="ticker-item" onclick="window.location.href='/commissions/upsc'">
            <strong>[UPSC]</strong> Civil Services Examination 2026 Notification Released — 1,056 Vacancies
          </span>
          <span class="ticker-item" onclick="window.location.href='/commissions/ssc'">
            <strong>[SSC]</strong> CGL 2026 Online Applications Open — 7,500+ Group B & C Posts
          </span>
          <span class="ticker-item" onclick="window.location.href='/commissions/railways'">
            <strong>[RRB]</strong> Railway NTPC Centralized Notice Published (CEN 05/2026) — 11,558 Vacancies
          </span>
          <span class="ticker-item" onclick="window.location.href='/commissions/banking'">
            <strong>[IBPS]</strong> Probationary Officers (CRP PO/MT-XVI) — 3,955 Bank Officer Posts
          </span>
          <span class="ticker-item" onclick="window.location.href='/commissions/defence'">
            <strong>[IAF]</strong> Air Force Common Admission Test (AFCAT 02/2026) Officer Entry Open
          </span>
        </div>
      </div>
    </div>
  </div>

  <!-- 2. Sticky Crisp Light Navbar (Zero # Anchors) -->
  <nav class="navbar">
    <div class="container nav-container">
      <a href="/" class="nav-brand" style="display: flex; align-items: center; gap: 0.75rem; text-decoration: none;">
        <img src="/assets/images/logo.png" alt="HamariJobs" style="width: 48px; height: 48px; object-fit: contain; display: block;">
        <div class="brand-title" style="font-family: var(--font-heading); font-size: 1.55rem; font-weight: 900; line-height: 1; color: var(--text-dark, #0f172a); letter-spacing: -0.5px;">
          Hamari<span style="color: var(--primary-red);">Jobs</span>
        </div>
      </a>

      <ul class="nav-links">
        <li><a href="/government-jobs" class="nav-link <?= str_starts_with($currentPath, '/government-jobs') || str_starts_with($currentPath, '/jobs') ? 'active' : '' ?>">Government Jobs</a></li>
        <li><a href="/commissions" class="nav-link <?= str_starts_with($currentPath, '/commissions') ? 'active' : '' ?>">Commissions</a></li>
        <li><a href="/exams" class="nav-link <?= str_starts_with($currentPath, '/exams') ? 'active' : '' ?>">Exam Hubs</a></li>
        <li><a href="/admit-cards" class="nav-link <?= str_starts_with($currentPath, '/admit-cards') ? 'active' : '' ?>">Admit Cards</a></li>
        <li><a href="/results" class="nav-link <?= str_starts_with($currentPath, '/results') ? 'active' : '' ?>">Results</a></li>
        <li><a href="/articles" class="nav-link <?= str_starts_with($currentPath, '/articles') ? 'active' : '' ?>">Guides</a></li>
      </ul>

      <div class="nav-actions">
        <button onclick="window.location.href='/government-jobs'" class="btn btn-outline btn-sm nav-desktop-search">
          <?= app_icon('search', '', 14) ?> Search
        </button>
        <a href="/government-jobs" class="btn btn-primary btn-sm nav-desktop-cta">
          <?= app_icon('zap', '', 14) ?> Latest Openings
        </a>
        <button id="navMobileSearchBtn" onclick="window.location.href='/government-jobs'" class="btn-icon-mobile" aria-label="Search Recruitments">
          <?= app_icon('search', '', 18) ?>
        </button>
        <button id="navHamburgerBtn" class="nav-hamburger-btn" aria-label="Toggle Navigation Menu">
          <span class="hamburger-bar"></span>
          <span class="hamburger-bar"></span>
          <span class="hamburger-bar"></span>
        </button>
      </div>
    </div>
  </nav>

  <!-- 3. Mobile Navigation Drawer & Backdrop (Touch Optimized) -->
  <div id="mobileDrawerBackdrop" class="mobile-drawer-backdrop"></div>
  <aside id="mobileDrawer" class="mobile-drawer" aria-hidden="true">
    <div class="mobile-drawer-header">
      <a href="/" class="mobile-drawer-brand">
        <img src="/assets/images/logo.png" alt="HamariJobs" style="width: 38px; height: 38px; object-fit: contain;">
        <div class="brand-title" style="font-family: var(--font-heading); font-size: 1.35rem; font-weight: 900; line-height: 1; color: var(--text-dark, #0f172a);">
          Hamari<span style="color: var(--primary-red);">Jobs</span>
        </div>
      </a>
      <button id="mobileDrawerCloseBtn" class="mobile-drawer-close-btn" aria-label="Close Navigation Menu">
        <?= app_icon('x', '', 20) ?>
      </button>
    </div>

    <div class="mobile-drawer-search">
      <form method="GET" action="/government-jobs" class="mobile-search-form">
        <div class="mobile-search-input-wrap">
          <span class="mobile-search-icon"><?= app_icon('search', '', 16) ?></span>
          <input type="text" name="q" placeholder="Search exams, posts, commissions..." class="form-control mobile-search-input">
          <button type="submit" class="btn btn-primary btn-sm mobile-search-submit">Go</button>
        </div>
      </form>
    </div>

    <div class="mobile-drawer-body">
      <div class="mobile-nav-group-label">OFFICIAL DIRECTORIES</div>
      <ul class="mobile-nav-list">
        <li>
          <a href="/government-jobs" class="mobile-nav-item <?= str_starts_with($currentPath, '/government-jobs') || str_starts_with($currentPath, '/jobs') ? 'active' : '' ?>">
            <span class="mobile-nav-icon"><?= app_icon('briefcase', '', 18) ?></span>
            <span class="mobile-nav-text">Government Jobs</span>
            <span class="mobile-nav-chevron">&rsaquo;</span>
          </a>
        </li>
        <li>
          <a href="/commissions" class="mobile-nav-item <?= str_starts_with($currentPath, '/commissions') ? 'active' : '' ?>">
            <span class="mobile-nav-icon"><?= app_icon('landmark', '', 18) ?></span>
            <span class="mobile-nav-text">Recruiting Commissions</span>
            <span class="mobile-nav-chevron">&rsaquo;</span>
          </a>
        </li>
        <li>
          <a href="/exams" class="mobile-nav-item <?= str_starts_with($currentPath, '/exams') ? 'active' : '' ?>">
            <span class="mobile-nav-icon"><?= app_icon('graduation-cap', '', 18) ?></span>
            <span class="mobile-nav-text">Exam Intelligence Hubs</span>
            <span class="mobile-nav-chevron">&rsaquo;</span>
          </a>
        </li>
        <li>
          <a href="/admit-cards" class="mobile-nav-item <?= str_starts_with($currentPath, '/admit-cards') ? 'active' : '' ?>">
            <span class="mobile-nav-icon"><?= app_icon('ticket', '', 18) ?></span>
            <span class="mobile-nav-text">Admit Cards & Hall Tickets</span>
            <span class="mobile-nav-chevron">&rsaquo;</span>
          </a>
        </li>
        <li>
          <a href="/results" class="mobile-nav-item <?= str_starts_with($currentPath, '/results') ? 'active' : '' ?>">
            <span class="mobile-nav-icon"><?= app_icon('award', '', 18) ?></span>
            <span class="mobile-nav-text">Exam Results & Scorecards</span>
            <span class="mobile-nav-chevron">&rsaquo;</span>
          </a>
        </li>
        <li>
          <a href="/articles" class="mobile-nav-item <?= str_starts_with($currentPath, '/articles') ? 'active' : '' ?>">
            <span class="mobile-nav-icon"><?= app_icon('book-open', '', 18) ?></span>
            <span class="mobile-nav-text">Preparation Guides & Analysis</span>
            <span class="mobile-nav-chevron">&rsaquo;</span>
          </a>
        </li>
      </ul>

      <div class="mobile-nav-group-label" style="margin-top: 1.5rem;">QUICK DISPATCH</div>
      <div class="mobile-quick-links">
        <a href="/commissions/upsc" class="mobile-quick-chip">UPSC</a>
        <a href="/commissions/ssc" class="mobile-quick-chip">SSC</a>
        <a href="/commissions/railways" class="mobile-quick-chip">Railways</a>
        <a href="/commissions/banking" class="mobile-quick-chip">Banking</a>
        <a href="/commissions/defence" class="mobile-quick-chip">Defence</a>
      </div>
    </div>

    <div class="mobile-drawer-footer">
      <a href="/government-jobs" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 0.75rem 1rem;">
        <?= app_icon('zap', '', 16) ?> Explore Active Openings
      </a>
      <div style="text-align: center; margin-top: 0.75rem; font-size: 0.75rem; color: var(--text-muted);">
        100% Verified Government Recruitment Data
      </div>
    </div>
  </aside>
