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
  <link rel="stylesheet" href="/assets/css/main.css">
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
        <button onclick="window.location.href='/government-jobs'" class="btn btn-outline btn-sm">
          <?= app_icon('search', '', 14) ?> Search
        </button>
        <a href="/government-jobs" class="btn btn-primary btn-sm">
          <?= app_icon('zap', '', 14) ?> Latest Openings
        </a>
      </div>
    </div>
  </nav>
