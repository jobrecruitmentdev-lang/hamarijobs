<?php
require_once __DIR__ . '/../../../backend/app/Database.php';
use App\Database;

$db = Database::getConnection();

$pageTitle = "Automation Console — Admin Control Center";
$adminPageTitle = "Automation Pipeline";
$adminPageHeading = "Live Pipeline Stage Dispatcher & Execution Telemetry";

require_once __DIR__ . '/partials/admin_layout_top.php';
?>

<!-- 1. Stage Dispatcher Cards -->
<div class="admin-stage-grid">
  
  <div class="admin-stage-card">
    <div>
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
        <span class="admin-badge badge-org" style="font-size: 0.7rem;">STAGE 1: CRAWLER</span>
        <span style="font-size: 1.25rem;">🕷️</span>
      </div>
      <h4 style="font-family: var(--font-heading); font-size: 1.05rem; font-weight: 800; color: var(--text-dark); margin-bottom: 0.35rem;">
        Gazette Multi-Source Ingestion
      </h4>
      <p style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.4;">
        Crawls official portals (UPSC, SSC, RRB, IBPS, Defence), runs bilingual OCR extraction, and creates structured recruitment records.
      </p>
    </div>
    <button class="admin-btn admin-btn-primary admin-btn-sm automation-trigger-btn" data-action="crawl" style="width: 100%;">
      ▶ Run Gazette Ingestion
    </button>
  </div>

  <div class="admin-stage-card">
    <div>
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
        <span class="admin-badge" style="background: var(--color-purple-bg); color: var(--color-purple); border: 1px solid var(--color-purple-border); font-size: 0.7rem; font-weight: 800;">STAGE 2: HUBS</span>
        <span style="font-size: 1.25rem;">🧠</span>
      </div>
      <h4 style="font-family: var(--font-heading); font-size: 1.05rem; font-weight: 800; color: var(--text-dark); margin-bottom: 0.35rem;">
        Rebuild Exam Intelligence Hubs
      </h4>
      <p style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.4;">
        Synthesizes examination patterns, tier schemes, syllabus weightages, and previous year cutoff trends from official gazettes.
      </p>
    </div>
    <button class="admin-btn admin-btn-outline admin-btn-sm automation-trigger-btn" data-action="exams" style="width: 100%;">
      🧠 Seed Exam Hubs
    </button>
  </div>

  <div class="admin-stage-card">
    <div>
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
        <span class="admin-badge" style="background: var(--color-blue-bg); color: var(--color-blue); border: 1px solid var(--color-blue-border); font-size: 0.7rem; font-weight: 800;">STAGE 3: SEO INDEX</span>
        <span style="font-size: 1.25rem;">🗺️</span>
      </div>
      <h4 style="font-family: var(--font-heading); font-size: 1.05rem; font-weight: 800; color: var(--text-dark); margin-bottom: 0.35rem;">
        Sitemaps & IndexNow Fast Ping
      </h4>
      <p style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.4;">
        Generates dynamic XML sitemaps for Jobs, Exams, and Articles, and dispatches instant IndexNow API pings to search engines.
      </p>
    </div>
    <button class="admin-btn admin-btn-glass admin-btn-sm automation-trigger-btn" data-action="sitemap" style="width: 100%;">
      🗺 Ping IndexNow Engine
    </button>
  </div>

</div>

<!-- 2. Interactive Terminal Console -->
<div class="admin-card" style="border: none; background: transparent; box-shadow: none;">
  <div class="admin-terminal-shell">
    <div class="admin-terminal-header">
      <div style="display: flex; align-items: center; gap: 0.75rem;">
        <div class="admin-terminal-dots">
          <span class="admin-terminal-dot red"></span>
          <span class="admin-terminal-dot yellow"></span>
          <span class="admin-terminal-dot green"></span>
        </div>
        <span class="admin-terminal-title">automation-daemon@govrecruit-node-01: ~</span>
      </div>
      <div style="display: flex; align-items: center; gap: 1rem;">
        <span id="automationStatusBadge" style="font-size: 0.75rem; font-weight: 800; color: #34d399; font-family: var(--font-mono);">
          [DAEMONS IDLE / READY]
        </span>
        <button onclick="document.getElementById('adminTerminalOutput').innerText = '[READY] Connected to local automation daemon. Dispatch a stage above.';" class="admin-btn admin-btn-glass admin-btn-sm" style="padding: 0.2rem 0.5rem; font-size: 0.725rem; color: #fca5a5; border-color: #334155;">
          Clear Terminal
        </button>
      </div>
    </div>
    <pre id="adminTerminalOutput" class="admin-terminal-body">[READY] Connected to Autonomous Recruitment Intelligence Orchestrator.
[INFO] Multi-worker threads initialized. Ready to execute pipeline stages on demand.
Select an automated task above to trigger live streaming telemetry.</pre>
  </div>
</div>

<?php require_once __DIR__ . '/partials/admin_layout_bottom.php'; ?>
