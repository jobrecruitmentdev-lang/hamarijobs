<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../../../backend/app/Controllers/AdminController.php';
use App\Controllers\AdminController;

if (!AdminController::isAuthenticated()) {
    header('Location: /admin/login');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$adminUser = $_SESSION['admin_user'] ?? ['username' => 'Admin', 'email' => 'admin@jobrecruitai.com'];
$currentAdminPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pageTitle = $pageTitle ?? "Government Recruitment Intelligence — Admin Control Center";
require_once __DIR__ . '/admin_icons.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
  <link rel="icon" type="image/png" href="/assets/images/logo.png">
  
  <!-- Modern Admin Stylesheet -->
  <link rel="stylesheet" href="/assets/css/admin.css?v=<?= file_exists(__DIR__ . '/../../../public/css/admin.css') ? filemtime(__DIR__ . '/../../../public/css/admin.css') : '2.1' ?>">

  <!-- Chart.js for High-Yield Analytics -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body>

<div class="admin-app-shell">

  <!-- Mobile Sidebar Backdrop -->
  <div id="adminSidebarBackdrop" class="admin-sidebar-backdrop"></div>

  <!-- 1. LEFT ADMIN NAVIGATION SIDEBAR -->
  <aside id="adminSidebar" class="admin-sidebar">
    <!-- Top Brand Emblem -->
    <div class="admin-sidebar-header">
        <a href="/admin/dashboard" class="admin-brand-link" style="display: flex; align-items: center; gap: 0.75rem;">
          <img src="/assets/images/logo.png" alt="HamariJobs" style="width: 38px; height: 38px; object-fit: contain;">
          <div>
            <div class="admin-brand-name">Hamari<span style="color: var(--brown-primary);">Jobs</span></div>
            <div class="admin-brand-pill">ADMIN CONSOLE</div>
          </div>
        </a>
        <button id="adminSidebarCloseBtn" class="admin-sidebar-close-btn" aria-label="Close Navigation">&times;</button>
      </div>

      <!-- Navigation Links -->
      <div class="admin-sidebar-body">
        
        <div>
          <div class="admin-nav-group-title">CORE OPERATIONS</div>
          <ul class="admin-nav-list">
            <li>
              <a href="/admin/dashboard" class="admin-nav-link <?= in_array($currentAdminPath, ['/admin', '/admin/dashboard']) ? 'active' : '' ?>">
                <div class="admin-nav-link-content">
                  <span class="admin-nav-icon"><?= admin_icon('dashboard', '', 18) ?></span>
                  <span>Dashboard & KPIs</span>
                </div>
              </a>
            </li>
            <li>
              <a href="/admin/recruitments" class="admin-nav-link <?= in_array($currentAdminPath, ['/admin/recruitments', '/admin/jobs']) ? 'active' : '' ?>">
                <div class="admin-nav-link-content">
                  <span class="admin-nav-icon"><?= admin_icon('briefcase', '', 18) ?></span>
                  <span>Government Jobs</span>
                </div>
                <span class="admin-nav-badge">CRUD</span>
              </a>
            </li>
            <li>
              <a href="/admin/commissions" class="admin-nav-link <?= $currentAdminPath === '/admin/commissions' ? 'active' : '' ?>">
                <div class="admin-nav-link-content">
                  <span class="admin-nav-icon"><?= admin_icon('landmark', '', 18) ?></span>
                  <span>Commissions</span>
                </div>
                <span class="admin-nav-badge">CRUD</span>
              </a>
            </li>
            <li>
              <a href="/admin/exams" class="admin-nav-link <?= $currentAdminPath === '/admin/exams' ? 'active' : '' ?>">
                <div class="admin-nav-link-content">
                  <span class="admin-nav-icon"><?= admin_icon('graduation-cap', '', 18) ?></span>
                  <span>Exam Hubs</span>
                </div>
                <span class="admin-nav-badge">CRUD</span>
              </a>
            </li>
            <li>
              <a href="/admin/admit-cards" class="admin-nav-link <?= $currentAdminPath === '/admin/admit-cards' ? 'active' : '' ?>">
                <div class="admin-nav-link-content">
                  <span class="admin-nav-icon"><?= admin_icon('ticket', '', 18) ?></span>
                  <span>Admit Cards</span>
                </div>
                <span class="admin-nav-badge">CRUD</span>
              </a>
            </li>
            <li>
              <a href="/admin/results" class="admin-nav-link <?= $currentAdminPath === '/admin/results' ? 'active' : '' ?>">
                <div class="admin-nav-link-content">
                  <span class="admin-nav-icon"><?= admin_icon('award', '', 18) ?></span>
                  <span>Results & Cutoffs</span>
                </div>
                <span class="admin-nav-badge">CRUD</span>
              </a>
            </li>
            <li>
              <a href="/admin/articles" class="admin-nav-link <?= in_array($currentAdminPath, ['/admin/articles', '/admin/guides']) ? 'active' : '' ?>">
                <div class="admin-nav-link-content">
                  <span class="admin-nav-icon"><?= admin_icon('book-open', '', 18) ?></span>
                  <span>Guides & Articles</span>
                </div>
                <span class="admin-nav-badge">CRUD</span>
              </a>
            </li>
          </ul>
        </div>

        <div>
          <div class="admin-nav-group-title">SYSTEM & ENGINES</div>
          <ul class="admin-nav-list">
            <li>
              <a href="/admin/sources" class="admin-nav-link <?= $currentAdminPath === '/admin/sources' ? 'active' : '' ?>">
                <div class="admin-nav-link-content">
                  <span class="admin-nav-icon"><?= admin_icon('globe', '', 18) ?></span>
                  <span>Monitored Sources</span>
                </div>
              </a>
            </li>
            <li>
              <a href="/admin/automation" class="admin-nav-link <?= $currentAdminPath === '/admin/automation' ? 'active' : '' ?>">
                <div class="admin-nav-link-content">
                  <span class="admin-nav-icon"><?= admin_icon('zap', '', 18) ?></span>
                  <span>Live Automation</span>
                </div>
                <span class="admin-nav-badge" style="background: rgba(16, 185, 129, 0.2); color: #34d399;">DAEMONS</span>
              </a>
            </li>
            <li style="margin-top: 0.65rem; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 0.5rem;">
              <a href="/" target="_blank" class="admin-nav-link" style="color: var(--primary-ruby); font-weight: 600;">
                <div class="admin-nav-link-content">
                  <span class="admin-nav-icon"><?= admin_icon('external-link', '', 18) ?></span>
                  <span>View Public Site ↗</span>
                </div>
              </a>
            </li>
          </ul>
        </div>


      </div>

    <!-- Sidebar Bottom User Profile Card -->
    <div class="admin-sidebar-footer">
      <div class="admin-user-card">
        <div class="admin-user-avatar">
          <?= strtoupper(substr($adminUser['username'] ?? 'A', 0, 1)) ?>
        </div>
        <div class="admin-user-info">
          <div class="admin-user-name"><?= htmlspecialchars($adminUser['username']) ?></div>
          <div class="admin-user-status">Online • Verified Operator</div>
        </div>
      </div>
      <div class="admin-user-actions">
        <a href="/admin/logout" class="admin-user-btn logout" style="width: 100%;" title="Secure Session Logout">
          <?= admin_icon('logout', '', 14) ?> Logout
        </a>
      </div>
    </div>
  </aside>

  <!-- 2. MAIN WORKSPACE CONTENT WRAPPER -->
  <div class="admin-main-wrapper">
    
    <!-- Top Header Bar -->
    <header class="admin-topbar">
      <div class="admin-topbar-left">
        <button id="adminMobileToggleBtn" class="admin-mobile-toggle-btn" aria-label="Toggle Navigation Sidebar">
          <?= admin_icon('menu', '', 18) ?>
        </button>
        <div>
          <div class="admin-breadcrumbs">
            <a href="/admin/dashboard">Admin Console</a>
            <span class="separator">/</span>
            <span><?= htmlspecialchars($adminPageTitle ?? 'Management') ?></span>
          </div>
          <h1 class="admin-page-heading"><?= htmlspecialchars($adminPageHeading ?? 'Control Center') ?></h1>
        </div>
      </div>

      <div class="admin-topbar-right">
        <div class="admin-status-indicator">
          <span class="admin-pulse-dot"></span>
          <span>System Healthy</span>
        </div>

        <a href="/" target="_blank" class="admin-btn admin-btn-glass admin-btn-sm" style="display: inline-flex; align-items: center; gap: 0.4rem; text-decoration: none; font-weight: 600;" title="Open Public Website in New Tab">
          <?= admin_icon('external-link', '', 14) ?>
          <span>View Public Site</span>
        </a>

        <?php if (!empty($adminHeaderActionHtml)): ?>
          <div class="admin-top-actions">
            <?= $adminHeaderActionHtml ?>
          </div>
        <?php endif; ?>
      </div>
    </header>

    <!-- Scrollable Workspace Body -->
    <main class="admin-content-scroll">
      <div class="admin-container">
