<?php
require_once __DIR__ . '/../../../backend/app/Database.php';
use App\Database;

$db = Database::getConnection();

$pageTitle = "Automation Console — Admin Control Center";
$adminPageTitle = "Automation Pipeline";
$adminPageHeading = "Autonomous 4-Hour Daemon, Gazette Ingestion & Telemetry";
require_once __DIR__ . '/partials/admin_icons.php';

require_once __DIR__ . '/partials/admin_layout_top.php';
?>

<!-- 0. Autonomous 4-Hour Daemon Controller Bar -->
<div class="admin-card" style="margin-bottom: 1.5rem; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #fff; border: 1px solid #334155;">
  <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
    <div>
      <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.35rem;">
        <span id="daemonStatusPill" class="admin-badge" style="background: rgba(100, 116, 139, 0.3); color: #cbd5e1; border: 1px solid #475569; font-size: 0.75rem; font-weight: 800;">
          CHECKING DAEMON...
        </span>
        <span style="font-size: 0.8rem; color: #94a3b8; font-family: var(--font-mono);">SYNC FREQUENCY: EVERY 4 HOURS</span>
      </div>
      <h3 style="font-family: var(--font-heading); font-size: 1.25rem; font-weight: 800; color: #f8fafc; margin: 0;">
        Autonomous Recruitment Ingestion Daemon
      </h3>
      <p style="font-size: 0.825rem; color: #94a3b8; margin: 0.25rem 0 0 0;">
        Runs fully hands-free in the background. Crawls official gazettes, checks SHA-256 change hashes, executes bilingual OCR, passes the Fact-Verification double-shield, and pings search engines.
      </p>
    </div>

    <div style="display: flex; align-items: center; gap: 1rem;">
      <div style="text-align: right; font-family: var(--font-mono); font-size: 0.775rem;">
        <div style="color: #94a3b8;">Last Run: <span id="daemonLastRun" style="color: #e2e8f0;">--</span></div>
        <div style="color: #94a3b8;">Next Run: <span id="daemonNextRun" style="color: #38bdf8;">--</span></div>
      </div>
      <button id="daemonToggleBtn" class="admin-btn admin-btn-primary" style="min-width: 140px; font-weight: 800;">
        Checking...
      </button>
    </div>
  </div>
</div>

<!-- 1. Stage Dispatcher Cards -->
<div class="admin-stage-grid">
  
  <div class="admin-stage-card">
    <div>
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
        <span class="admin-badge badge-org" style="font-size: 0.7rem;">STAGE 1: CRAWLER</span>
        <span style="color: var(--primary-ruby);"><?= admin_icon('globe', '', 20) ?></span>
      </div>
      <h4 style="font-family: var(--font-heading); font-size: 1.05rem; font-weight: 800; color: var(--text-dark); margin-bottom: 0.35rem;">
        Gazette Multi-Source Ingestion
      </h4>
      <p style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.4;">
        Crawls official portals (UPSC, SSC, RRB, IBPS, Defence), verifies SHA-256 hashes, runs bilingual OCR, and persists structured jobs.
      </p>
    </div>
    <button class="admin-btn admin-btn-primary admin-btn-sm automation-trigger-btn" data-action="crawl" style="width: 100%;">
      <?= admin_icon('zap', '', 14) ?> Run Gazette Ingestion
    </button>
  </div>

  <div class="admin-stage-card">
    <div>
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
        <span class="admin-badge" style="background: var(--color-purple-bg); color: var(--color-purple); border: 1px solid var(--color-purple-border); font-size: 0.7rem; font-weight: 800;">STAGE 2: HUBS</span>
        <span style="color: var(--color-purple);"><?= admin_icon('graduation-cap', '', 20) ?></span>
      </div>
      <h4 style="font-family: var(--font-heading); font-size: 1.05rem; font-weight: 800; color: var(--text-dark); margin-bottom: 0.35rem;">
        Rebuild Exam Intelligence Hubs
      </h4>
      <p style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.4;">
        Synthesizes examination patterns, tier schemes, syllabus weightages, and previous year cutoff trends from official gazettes.
      </p>
    </div>
    <button class="admin-btn admin-btn-outline admin-btn-sm automation-trigger-btn" data-action="exams" style="width: 100%;">
      <?= admin_icon('graduation-cap', '', 14) ?> Seed Exam Hubs
    </button>
  </div>

  <div class="admin-stage-card">
    <div>
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
        <span class="admin-badge" style="background: var(--color-blue-bg); color: var(--color-blue); border: 1px solid var(--color-blue-border); font-size: 0.7rem; font-weight: 800;">STAGE 3: SEO INDEX</span>
        <span style="color: var(--color-blue);"><?= admin_icon('zap', '', 20) ?></span>
      </div>
      <h4 style="font-family: var(--font-heading); font-size: 1.05rem; font-weight: 800; color: var(--text-dark); margin-bottom: 0.35rem;">
        Sitemaps & Dual-Engine Indexing
      </h4>
      <p style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.4;">
        Regenerates XML sitemaps for Jobs, Exams, and Articles, and dispatches instant IndexNow & Google Indexing API pings.
      </p>
    </div>
    <button class="admin-btn admin-btn-glass admin-btn-sm automation-trigger-btn" data-action="sitemap" style="width: 100%;">
      <?= admin_icon('zap', '', 14) ?> Ping Indexing Engines
    </button>
  </div>

</div>

<!-- 2. Interactive Terminal Console -->
<div class="admin-card" style="border: none; background: transparent; box-shadow: none; margin-bottom: 1.5rem;">
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
[INFO] SHA-256 Hash Detector and Fact-Verification Double-Shield active.
[INFO] Dual Search Indexing Engine (IndexNow + Google Indexing API) armed.
Select an automated task above or toggle the Autonomous Daemon to trigger live telemetry.</pre>
  </div>
</div>

<!-- 3. Quarantine Review Queue (Double-Shield Anti-Hallucination) -->
<div class="admin-card" style="margin-bottom: 1.5rem;">
  <div class="admin-card-header" style="display: flex; align-items: center; justify-content: space-between;">
    <div>
      <h3 class="admin-card-title" style="display: flex; align-items: center; gap: 0.5rem;">
        <?= admin_icon('shield', '', 18) ?> Quarantine Review Queue (Fact-Verification Shield)
      </h3>
      <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">
        Notices flagged with domain mismatches, abnormal vacancy counts, or reverse application dates are isolated here for manual verification.
      </p>
    </div>
    <button onclick="loadReviewQueue()" class="admin-btn admin-btn-outline admin-btn-sm">
      Refresh Queue
    </button>
  </div>

  <div id="reviewQueueContainer" style="overflow-x: auto;">
    <div style="text-align: center; padding: 2rem; color: var(--text-muted);">
      Loading quarantine queue...
    </div>
  </div>
</div>

<!-- 4. Database Execution Telemetry (automation_runs) -->
<div class="admin-card">
  <div class="admin-card-header" style="display: flex; align-items: center; justify-content: space-between;">
    <div>
      <h3 class="admin-card-title" style="display: flex; align-items: center; gap: 0.5rem;">
        <?= admin_icon('activity', '', 18) ?> Database Execution Telemetry (MySQL: `automation_runs`)
      </h3>
      <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">
        Audited pipeline execution logs, notices parsed, SHA-256 skips, and processing runtimes.
      </p>
    </div>
    <button onclick="loadAutomationRuns()" class="admin-btn admin-btn-outline admin-btn-sm">
      Refresh Telemetry
    </button>
  </div>

  <div id="telemetryTableContainer" style="overflow-x: auto;">
    <div style="text-align: center; padding: 2rem; color: var(--text-muted);">
      Loading execution history from database...
    </div>
  </div>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

// 1. Daemon Status & Toggle
async function fetchDaemonStatus() {
  try {
    const res = await fetch('/api/v1/admin/daemon/status');
    const json = await res.json();
    if (json.success && json.data) {
      const state = json.data;
      const pill = document.getElementById('daemonStatusPill');
      const btn = document.getElementById('daemonToggleBtn');
      const lastRun = document.getElementById('daemonLastRun');
      const nextRun = document.getElementById('daemonNextRun');

      lastRun.innerText = state.last_run_at || 'None';
      nextRun.innerText = state.next_run_at || 'Idle';

      if (state.status === 'RUNNING') {
        pill.innerText = `● DAEMON ACTIVE (PID: ${state.pid || 'Active'})`;
        pill.style.background = 'rgba(16, 185, 129, 0.2)';
        pill.style.color = '#34d399';
        pill.style.borderColor = 'rgba(16, 185, 129, 0.4)';

        btn.innerText = 'Stop 4-Hour Daemon';
        btn.className = 'admin-btn admin-btn-danger';
        btn.onclick = () => toggleDaemon('stop');
      } else {
        pill.innerText = '○ DAEMON STOPPED';
        pill.style.background = 'rgba(100, 116, 139, 0.2)';
        pill.style.color = '#94a3b8';
        pill.style.borderColor = '#475569';

        btn.innerText = 'Start 4-Hour Daemon';
        btn.className = 'admin-btn admin-btn-primary';
        btn.onclick = () => toggleDaemon('start');
      }
    }
  } catch (err) {
    console.error('Error fetching daemon status:', err);
  }
}

async function toggleDaemon(action) {
  const btn = document.getElementById('daemonToggleBtn');
  btn.disabled = true;
  btn.innerText = action === 'start' ? 'Starting Daemon...' : 'Stopping Daemon...';

  try {
    const token = getCsrfToken();
    const res = await fetch('/api/v1/admin/daemon/toggle', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': token,
        'Accept': 'application/json'
      },
      credentials: 'same-origin',
      body: JSON.stringify({ action: action, csrf_token: token })
    });
    const json = await res.json();
    if (json.success) {
      showAutomationToast(json.message || `Daemon ${action === 'start' ? 'started' : 'stopped'} successfully!`, 'success');
    } else {
      showAutomationToast(json.error || 'Failed to update daemon', 'error');
    }
    await fetchDaemonStatus();
    loadAutomationRuns();
  } catch (err) {
    showAutomationToast('Failed to update daemon: ' + err.message, 'error');
  } finally {
    btn.disabled = false;
  }
}

// 2. Quarantine Review Queue
async function loadReviewQueue() {
  const container = document.getElementById('reviewQueueContainer');
  try {
    const res = await fetch('/api/v1/admin/review-queue');
    const json = await res.json();
    if (!json.success || !json.data || json.data.length === 0) {
      container.innerHTML = `
        <div style="text-align: center; padding: 2.5rem 1rem; color: #10b981;">
          <div style="font-size: 1.75rem; margin-bottom: 0.5rem;">🛡️</div>
          <div style="font-weight: 700; font-size: 1rem; color: var(--text-dark);">Quarantine Queue Clean</div>
          <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.25rem;">
            Fact-Verification Double-Shield active. All active gazettes passed government domain and vacancy consistency checks.
          </div>
        </div>
      `;
      return;
    }

    let html = `
      <table class="admin-table" style="width: 100%;">
        <thead>
          <tr>
            <th>Notice Title & Org</th>
            <th>Vacancies</th>
            <th>Anomaly Flags</th>
            <th>Official URL</th>
            <th>Date Added</th>
            <th style="text-align: right;">Action</th>
          </tr>
        </thead>
        <tbody>
    `;

    json.data.forEach(item => {
      const flags = (item.anomaly_flags || 'FLAGGED').split(',').map(f => 
        `<span class="admin-badge" style="background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; font-size: 0.675rem; margin-right: 0.25rem;">${f.trim()}</span>`
      ).join('');

      html += `
        <tr id="reviewRow_${item.id}" style="transition: all 0.35s ease;">
          <td>
            <div style="font-weight: 700; color: var(--text-dark); font-size: 0.9rem;">${escapeHtml(item.title)}</div>
            <div style="font-size: 0.75rem; color: var(--text-muted);">${escapeHtml(item.organization_name || 'Govt Portal')}</div>
          </td>
          <td style="font-weight: 700; font-size: 0.875rem;">${item.total_vacancies ? Number(item.total_vacancies).toLocaleString() : 'N/A'}</td>
          <td>${flags}</td>
          <td>
            <a href="${escapeHtml(item.official_apply_url || item.primary_notification_url || '#')}" target="_blank" style="color: var(--primary-ruby); font-size: 0.8rem; text-decoration: underline;">
              View Source ↗
            </a>
          </td>
          <td style="font-size: 0.775rem; color: var(--text-muted);">${item.created_at || 'Just now'}</td>
          <td style="text-align: right; white-space: nowrap;">
            <button onclick="approveReviewItem(${item.id}, this)" class="admin-btn admin-btn-sm" style="background: #10b981; color: #fff; font-size: 0.75rem; padding: 0.28rem 0.65rem; margin-right: 0.35rem; cursor: pointer; transition: all 0.2s ease;">
              ✓ Approve
            </button>
            <button onclick="rejectReviewItem(${item.id}, this)" class="admin-btn admin-btn-sm admin-btn-danger" style="font-size: 0.75rem; padding: 0.28rem 0.65rem; cursor: pointer; transition: all 0.2s ease;">
              ✕ Reject
            </button>
          </td>
        </tr>
      `;
    });

    html += `</tbody></table>`;
    container.innerHTML = html;
  } catch (err) {
    container.innerHTML = `<div style="padding: 1rem; color: #ef4444;">Error loading quarantine queue: ${err.message}</div>`;
  }
}

// Non-blocking, modern notification toast
function showAutomationToast(message, type = 'success') {
  let toastContainer = document.getElementById('automationToastContainer');
  if (!toastContainer) {
    toastContainer = document.createElement('div');
    toastContainer.id = 'automationToastContainer';
    toastContainer.style.cssText = 'position: fixed; top: 24px; right: 24px; z-index: 99999; display: flex; flex-direction: column; gap: 10px; max-width: 400px; pointer-events: none;';
    document.body.appendChild(toastContainer);
  }

  const toast = document.createElement('div');
  const accentColor = type === 'success' ? '#10b981' : (type === 'error' ? '#ef4444' : '#3b82f6');
  const icon = type === 'success' ? '✓' : (type === 'error' ? '✕' : 'ℹ');

  toast.style.cssText = `
    background: #0f172a;
    color: #f8fafc;
    border-left: 4px solid ${accentColor};
    padding: 12px 18px;
    border-radius: 8px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4), 0 8px 10px -6px rgba(0, 0, 0, 0.4);
    font-size: 0.85rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 12px;
    pointer-events: auto;
    opacity: 0;
    transform: translateY(-12px);
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  `;
  toast.innerHTML = `<span style="color: ${accentColor}; font-size: 1.15rem; font-weight: 800;">${icon}</span> <span style="flex: 1; line-height: 1.35;">${message}</span>`;
  toastContainer.appendChild(toast);

  requestAnimationFrame(() => {
    toast.style.opacity = '1';
    toast.style.transform = 'translateY(0)';
  });

  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(-12px)';
    setTimeout(() => toast.remove(), 320);
  }, 4500);
}

function getCsrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

async function approveReviewItem(id, btnElement = null) {
  const token = getCsrfToken();
  const row = btnElement ? (btnElement.closest('tr') || document.getElementById(`reviewRow_${id}`)) : document.getElementById(`reviewRow_${id}`);
  const originalText = btnElement ? btnElement.innerHTML : '✓ Approve';

  if (btnElement) {
    btnElement.disabled = true;
    btnElement.innerHTML = '⏳ Approving...';
    btnElement.style.opacity = '0.75';
    btnElement.style.cursor = 'wait';
  }

  try {
    const res = await fetch('/api/v1/admin/review-queue/approve', {
      method: 'POST',
      headers: { 
        'Content-Type': 'application/json', 
        'X-CSRF-Token': token,
        'Accept': 'application/json'
      },
      credentials: 'same-origin',
      body: JSON.stringify({ id: id, csrf_token: token })
    });

    const json = await res.json();
    if (json.success) {
      if (btnElement) {
        btnElement.innerHTML = '✓ Approved!';
        btnElement.style.background = '#059669';
      }
      showAutomationToast(json.message || `Recruitment #${id} approved and published live!`, 'success');

      if (row) {
        row.style.opacity = '0';
        row.style.transform = 'translateX(30px)';
        setTimeout(() => {
          loadReviewQueue();
          loadAutomationRuns();
        }, 350);
      } else {
        loadReviewQueue();
        loadAutomationRuns();
      }
    } else {
      if (btnElement) {
        btnElement.disabled = false;
        btnElement.innerHTML = originalText;
        btnElement.style.opacity = '1';
        btnElement.style.cursor = 'pointer';
      }
      showAutomationToast(json.error || 'Failed to approve item', 'error');
    }
  } catch (err) {
    if (btnElement) {
      btnElement.disabled = false;
      btnElement.innerHTML = originalText;
      btnElement.style.opacity = '1';
      btnElement.style.cursor = 'pointer';
    }
    showAutomationToast('Server or network error: ' + err.message, 'error');
  }
}

async function rejectReviewItem(id, btnElement = null) {
  const token = getCsrfToken();
  const row = btnElement ? (btnElement.closest('tr') || document.getElementById(`reviewRow_${id}`)) : document.getElementById(`reviewRow_${id}`);
  const originalText = btnElement ? btnElement.innerHTML : '✕ Reject';

  if (btnElement) {
    btnElement.disabled = true;
    btnElement.innerHTML = '⏳ Rejecting...';
    btnElement.style.opacity = '0.75';
    btnElement.style.cursor = 'wait';
  }

  try {
    const res = await fetch('/api/v1/admin/review-queue/reject', {
      method: 'POST',
      headers: { 
        'Content-Type': 'application/json', 
        'X-CSRF-Token': token,
        'Accept': 'application/json'
      },
      credentials: 'same-origin',
      body: JSON.stringify({ id: id, csrf_token: token })
    });

    const json = await res.json();
    if (json.success) {
      if (btnElement) {
        btnElement.innerHTML = '✕ Rejected!';
        btnElement.style.background = '#991b1b';
      }
      showAutomationToast(json.message || `Recruitment #${id} rejected and archived.`, 'info');

      if (row) {
        row.style.opacity = '0';
        row.style.transform = 'translateX(-30px)';
        setTimeout(() => {
          loadReviewQueue();
          loadAutomationRuns();
        }, 350);
      } else {
        loadReviewQueue();
        loadAutomationRuns();
      }
    } else {
      if (btnElement) {
        btnElement.disabled = false;
        btnElement.innerHTML = originalText;
        btnElement.style.opacity = '1';
        btnElement.style.cursor = 'pointer';
      }
      showAutomationToast(json.error || 'Failed to reject item', 'error');
    }
  } catch (err) {
    if (btnElement) {
      btnElement.disabled = false;
      btnElement.innerHTML = originalText;
      btnElement.style.opacity = '1';
      btnElement.style.cursor = 'pointer';
    }
    showAutomationToast('Server or network error: ' + err.message, 'error');
  }
}

// 3. Database Execution Telemetry (automation_runs)
async function loadAutomationRuns() {
  const container = document.getElementById('telemetryTableContainer');
  try {
    const res = await fetch('/api/v1/admin/automation-runs');
    const json = await res.json();
    if (!json.success || !json.data || json.data.length === 0) {
      container.innerHTML = `<div style="text-align: center; padding: 2rem; color: var(--text-muted);">No automation run records in database yet. Trigger a stage above or start the daemon.</div>`;
      return;
    }

    let html = `
      <table class="admin-table" style="width: 100%;">
        <thead>
          <tr>
            <th>Run ID</th>
            <th>Stage</th>
            <th>Trigger Source</th>
            <th>Status</th>
            <th>Notices</th>
            <th>Ingested</th>
            <th>Skipped (SHA-256)</th>
            <th>Quarantined</th>
            <th>Duration</th>
            <th>Started At</th>
          </tr>
        </thead>
        <tbody>
    `;

    json.data.forEach(r => {
      const statusColor = r.status === 'SUCCESS' ? '#10b981' : (r.status === 'RUNNING' ? '#f59e0b' : '#ef4444');
      const statusBg = r.status === 'SUCCESS' ? 'rgba(16, 185, 129, 0.1)' : (r.status === 'RUNNING' ? 'rgba(245, 158, 11, 0.1)' : 'rgba(239, 68, 68, 0.1)');
      const shortUuid = r.run_uuid ? r.run_uuid.substring(0, 8) : `#${r.id}`;

      html += `
        <tr>
          <td style="font-family: var(--font-mono); font-size: 0.8rem; font-weight: 700;">${shortUuid}</td>
          <td style="font-weight: 600; font-size: 0.85rem;">${escapeHtml(r.stage_name || 'LIVE_INGESTION')}</td>
          <td><span class="admin-badge" style="font-size: 0.675rem; background: var(--color-blue-bg); color: var(--color-blue);">${escapeHtml(r.trigger_source || 'MANUAL')}</span></td>
          <td><span class="admin-badge" style="background: ${statusBg}; color: ${statusColor}; font-weight: 800; font-size: 0.7rem;">${r.status}</span></td>
          <td style="font-weight: 700;">${r.notices_found || 0}</td>
          <td style="color: #10b981; font-weight: 700;">+${r.new_ingested || 0}</td>
          <td style="color: #64748b; font-size: 0.8rem;">${r.skipped_unchanged || 0}</td>
          <td style="${(r.quarantined > 0) ? 'color: #ef4444; font-weight: 800;' : 'color: #94a3b8;'}">${r.quarantined || 0}</td>
          <td style="font-family: var(--font-mono); font-size: 0.8rem;">${r.elapsed_seconds ? r.elapsed_seconds + 's' : '--'}</td>
          <td style="font-size: 0.775rem; color: var(--text-muted);">${r.started_at || '--'}</td>
        </tr>
      `;
    });

    html += `</tbody></table>`;
    container.innerHTML = html;
  } catch (err) {
    container.innerHTML = `<div style="padding: 1rem; color: #ef4444;">Error loading telemetry: ${err.message}</div>`;
  }
}

// 4. Manual Trigger Handler
document.querySelectorAll('.automation-trigger-btn').forEach(btn => {
  btn.addEventListener('click', async function() {
    const action = this.getAttribute('data-action');
    const terminal = document.getElementById('adminTerminalOutput');
    const badge = document.getElementById('automationStatusBadge');
    
    document.querySelectorAll('.automation-trigger-btn').forEach(b => b.disabled = true);
    this.innerText = '⚡ Running Stage...';
    
    if (badge) {
      badge.innerText = `[STAGE: ${action.toUpperCase()} EXECUTING...]`;
      badge.style.color = '#fbbf24';
    }
    
    terminal.innerText += `\n\n>>> [DISPATCH] Initiating stage '${action}' at ${new Date().toLocaleTimeString()}...\n`;
    terminal.scrollTop = terminal.scrollHeight;
    
    try {
      const res = await fetch(`/api/v1/admin/trigger?action=${encodeURIComponent(action)}`, {
        method: 'POST',
        headers: { 
          'X-CSRF-Token': getCsrfToken(),
          'Accept': 'application/json'
        },
        credentials: 'same-origin'
      });
      const data = await res.json();
      
      terminal.innerText += `[COMMAND] ${data.command || action}\n`;
      terminal.innerText += `[OUTPUT]\n${data.output || 'Completed with no stdout.'}\n`;
      
      if (data.success) {
        terminal.innerText += `\n✓ Stage '${action}' completed successfully at ${data.timestamp || new Date().toLocaleTimeString()}.\n`;
        if (badge) {
          badge.innerText = `[SUCCESS: ${action.toUpperCase()} COMPLETED]`;
          badge.style.color = '#34d399';
        }
      } else {
        terminal.innerText += `\n⚠ Stage '${action}' completed with notice.\n`;
        if (badge) {
          badge.innerText = `[DONE]`;
          badge.style.color = '#38bdf8';
        }
      }
    } catch (err) {
      terminal.innerText += `\n❌ Execution error: ${err.message}\n`;
      if (badge) {
        badge.innerText = `[DAEMON ERROR]`;
        badge.style.color = '#f87171';
      }
    } finally {
      document.querySelectorAll('.automation-trigger-btn').forEach(b => {
        b.disabled = false;
        const act = b.getAttribute('data-action');
        if (act === 'crawl') b.innerHTML = '<?= admin_icon('zap', '', 14) ?> Run Gazette Ingestion';
        else if (act === 'exams') b.innerHTML = '<?= admin_icon('graduation-cap', '', 14) ?> Seed Exam Hubs';
        else if (act === 'sitemap') b.innerHTML = '<?= admin_icon('zap', '', 14) ?> Ping Indexing Engines';
      });
      terminal.scrollTop = terminal.scrollHeight;
      loadReviewQueue();
      loadAutomationRuns();
    }
  });
});

function escapeHtml(str) {
  if (!str) return '';
  return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// Initial Boot
fetchDaemonStatus();
loadReviewQueue();
loadAutomationRuns();
setInterval(fetchDaemonStatus, 15000);
</script>

<?php require_once __DIR__ . '/partials/admin_layout_bottom.php'; ?>
