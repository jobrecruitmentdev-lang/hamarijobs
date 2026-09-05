<?php
require_once __DIR__ . '/../../../backend/app/Database.php';
use App\Database;

$db = Database::getConnection();

// Fetch all Admit Card & Exam Date timeline events
$events = $db->query("
    SELECT re.*, r.title as recruitment_title, r.slug as recruitment_slug, COALESCE(r.organization_name, re.organization_name, 'Official') as effective_org
    FROM recruitment_events re
    LEFT JOIN recruitments r ON re.recruitment_id = r.id
    WHERE re.event_type IN ('ADMIT_CARD_RELEASED', 'EXAM_DATE', 'CORRECTION_WINDOW_OPENED')
    ORDER BY re.event_date DESC, re.id DESC
")->fetchAll();

// Fetch all active recruitments for modal dropdown picker
$recruitmentsList = $db->query("
    SELECT id, title, organization_name, advertisement_number, year 
    FROM recruitments 
    ORDER BY updated_at DESC 
    LIMIT 100
")->fetchAll();

$pageTitle = "Manage Admit Cards & Exam Dates — Admin Control Center";
$adminPageTitle = "Admit Cards";
$adminPageHeading = "Admit Cards, Hall Tickets & Exam Schedule Intelligence";
require_once __DIR__ . '/partials/admin_icons.php';
$adminHeaderActionHtml = '<button onclick="openModal(\'addEventModal\')" class="admin-btn admin-btn-primary admin-btn-sm">' . admin_icon('plus', '', 14) . ' Create Admit Card Notice</button>';

require_once __DIR__ . '/partials/admin_layout_top.php';
?>

<!-- Top Statistics KPI Cards -->
<div class="admin-kpi-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); margin-bottom: 1.75rem;">
  <div class="admin-kpi-card">
    <div class="admin-kpi-header">
      <span class="admin-kpi-label">Total Notices</span>
      <div class="admin-kpi-icon ruby"><?= admin_icon('ticket', '', 18) ?></div>
    </div>
    <div class="admin-kpi-value" style="color: var(--primary-ruby);"><?= number_format(count($events)) ?></div>
    <div class="admin-kpi-subtext">
      Tracked Milestones & Event Gazettes
    </div>
  </div>

  <div class="admin-kpi-card">
    <div class="admin-kpi-header">
      <span class="admin-kpi-label">Live Admit Cards</span>
      <div class="admin-kpi-icon emerald"><?= admin_icon('zap', '', 18) ?></div>
    </div>
    <div class="admin-kpi-value" style="color: var(--color-emerald);"><?= number_format(count(array_filter($events, fn($e) => $e['event_type'] === 'ADMIT_CARD_RELEASED'))) ?></div>
    <div class="admin-kpi-subtext">
      <span style="color: var(--color-emerald); font-weight: 700;">● Active</span> Hall Tickets for Download
    </div>
  </div>

  <div class="admin-kpi-card">
    <div class="admin-kpi-header">
      <span class="admin-kpi-label">Exam Dates Notified</span>
      <div class="admin-kpi-icon blue"><?= admin_icon('calendar', '', 18) ?></div>
    </div>
    <div class="admin-kpi-value" style="color: var(--color-blue);"><?= number_format(count(array_filter($events, fn($e) => $e['event_type'] === 'EXAM_DATE'))) ?></div>
    <div class="admin-kpi-subtext">
      Scheduled Examination Dates
    </div>
  </div>
</div>

<!-- Admit Cards Table Card -->
<div class="admin-card">
  <div class="admin-card-header">
    <div class="admin-card-title-wrap">
      <h3 class="admin-card-title"><?= admin_icon('ticket', '', 18) ?> Official Admit Cards & Hall Tickets Tracker</h3>
      <p class="admin-card-desc">Showing <strong><?= count($events) ?></strong> published hall tickets and exam schedules across national recruiting agencies</p>
    </div>
    <button onclick="openModal('addEventModal')" class="admin-btn admin-btn-primary admin-btn-sm">
      <?= admin_icon('plus', '', 14) ?> Create Admit Card Notice
    </button>
  </div>

  <div class="admin-table-responsive">
    <table class="admin-table">
      <thead>
        <tr>
          <th style="width: 60px;">ID</th>
          <th>Event Title / Notice</th>
          <th>Organization / Recruitment</th>
          <th>Notice Type</th>
          <th>Release / Exam Date</th>
          <th>Live Status</th>
          <th>Official Link</th>
          <th style="text-align: right;">Actions</th>
        </tr>
      </thead>
      <tbody id="eventsTableBody">
        <?php if (empty($events)): ?>
          <tr id="emptyRow">
            <td colspan="7" style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
              <div style="font-size: 2rem; margin-bottom: 0.5rem;"></div>
              <strong>No admit card notices published yet.</strong>
              <p style="font-size: 0.8rem; margin-top: 0.35rem;">Click "Create Admit Card Notice" above to publish a new hall ticket or exam date.</p>
            </td>
          </tr>
        <?php endif; ?>

        <?php foreach ($events as $ev): ?>
          <tr id="event-row-<?= $ev['id'] ?>">
            <td>
              <span class="admin-id-badge">#<?= $ev['id'] ?></span>
            </td>
            <td>
              <div style="font-weight: 700; color: var(--text-dark); font-size: 0.95rem;">
                <?= htmlspecialchars($ev['event_title']) ?>
              </div>
              <?php if (!empty($ev['details'])): ?>
                <div style="font-size: 0.775rem; color: var(--text-muted); margin-top: 0.2rem; max-width: 350px;">
                  <?= htmlspecialchars(substr($ev['details'], 0, 70)) ?><?= strlen($ev['details']) > 70 ? '...' : '' ?>
                </div>
              <?php endif; ?>
            </td>
            <td>
              <span class="admin-badge badge-org" style="font-size: 0.72rem; font-weight: 800;">
                <?= htmlspecialchars($ev['effective_org']) ?>
              </span>
              <?php if (!empty($ev['recruitment_title'])): ?>
                <div style="font-size: 0.775rem; color: var(--text-secondary); margin-top: 0.25rem;">
                  <?= htmlspecialchars(substr($ev['recruitment_title'], 0, 45)) ?><?= strlen($ev['recruitment_title']) > 45 ? '...' : '' ?>
                </div>
              <?php endif; ?>
            </td>
            <td>
              <?php
                $badgeClass = 'badge-active';
                $label = 'Admit Card';
                if ($ev['event_type'] === 'EXAM_DATE') {
                    $badgeClass = 'badge-urgent';
                    $label = '📅 Exam Date';
                } elseif ($ev['event_type'] === 'CORRECTION_WINDOW_OPENED') {
                    $badgeClass = 'badge-org';
                    $label = ' Correction';
                }
              ?>
              <span class="admin-badge <?= $badgeClass ?>" style="font-size: 0.72rem;">
                <?= $label ?>
              </span>
            </td>
            <td>
              <div style="font-weight: 700; color: var(--primary-ruby); font-size: 0.9rem;">
                <?= !empty($ev['event_date']) ? date('d M Y', strtotime($ev['event_date'])) : 'TBD' ?>
              </div>
              <?php if (!empty($ev['is_tentative'])): ?>
                <span style="font-size: 0.7rem; color: var(--text-muted);">(Tentative)</span>
              <?php endif; ?>
            </td>
            <td>
              <?php $evStatus = strtoupper(trim($ev['status'] ?? 'RELEASED')); ?>
              <select class="admin-form-control event-status-select" data-event-id="<?= $ev['id'] ?>" style="padding: 0.3rem 0.5rem; font-size: 0.75rem; width: 145px; font-weight: 700;">
                <option value="RELEASED" <?= $evStatus === 'RELEASED' ? 'selected' : '' ?>>✓ Available Now</option>
                <option value="EXPECTED" <?= $evStatus === 'EXPECTED' ? 'selected' : '' ?>>⏳ Releasing Soon</option>
                <option value="CITY_SLIP" <?= $evStatus === 'CITY_SLIP' ? 'selected' : '' ?>>🗺️ City Slip Out</option>
                <option value="POSTPONED" <?= $evStatus === 'POSTPONED' ? 'selected' : '' ?>>⚠️ Postponed</option>
              </select>
            </td>
            <td>
              <?php if (!empty($ev['reference_url'])): ?>
                <a href="<?= htmlspecialchars($ev['reference_url']) ?>" target="_blank" class="admin-btn admin-btn-glass admin-btn-sm" style="font-size: 0.75rem; padding: 0.35rem 0.65rem;">
                  Portal ↗
                </a>
              <?php else: ?>
                <span style="color: var(--text-muted); font-size: 0.8rem;">—</span>
              <?php endif; ?>
            </td>
            <td style="text-align: right;">
              <div class="admin-action-btn-group" style="justify-content: flex-end;">
                <button onclick="editEvent(<?= $ev['id'] ?>)" class="admin-btn admin-btn-glass admin-btn-icon-only" title="Edit Event Notice">
                  <?= admin_icon('edit', '', 15) ?>
                </button>
                <button onclick="deleteEvent(<?= $ev['id'] ?>)" class="admin-btn admin-btn-danger admin-btn-icon-only" title="Delete Notice">
                  <?= admin_icon('trash', '', 15) ?>
                </button>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Event Modal -->
<div id="addEventModal" class="admin-modal-overlay">
  <div class="admin-modal-card">
    <div class="admin-modal-header">
      <h3 class="admin-modal-title"><?= admin_icon('plus', '', 18) ?> Publish Admit Card or Exam Notice</h3>
      <button class="admin-modal-close-btn" onclick="closeModal('addEventModal')">&times;</button>
    </div>

    <form id="addEventForm">
      <div class="admin-modal-body">
        
        <div class="admin-form-section-title">1. NOTICE DETAILS</div>
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
          <div class="admin-form-group">
            <label class="admin-form-label">Event / Notice Title *</label>
            <input type="text" name="event_title" required placeholder="e.g. SSC CGL 2026 Tier 1 Admit Card Released" class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Notice Type *</label>
            <select name="event_type" required class="admin-form-control">
              <option value="ADMIT_CARD_RELEASED">Admit Card Released</option>
              <option value="EXAM_DATE">Exam Date Notified</option>
              <option value="CORRECTION_WINDOW_OPENED">Correction Window Opened</option>
            </select>
          </div>
        </div>

        <div class="admin-form-section-title">2. RECRUITMENT & ORGANIZATION LINKING</div>
        <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 1rem;">
          <div class="admin-form-group">
            <label class="admin-form-label">Link to Existing Recruitment (Optional)</label>
            <select name="recruitment_id" id="add_event_rec_id" class="admin-form-control" onchange="autoFillOrg(this)">
              <option value="">-- Standalone / Unlinked Entry --</option>
              <?php foreach ($recruitmentsList as $r): ?>
                <option value="<?= $r['id'] ?>" data-org="<?= htmlspecialchars($r['organization_name']) ?>">
                  #<?= $r['id'] ?> - <?= htmlspecialchars($r['organization_name']) ?>: <?= htmlspecialchars($r['title']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Organization / Commission Name</label>
            <input type="text" name="organization_name" id="add_event_org_name" placeholder="e.g. Staff Selection Commission" class="admin-form-control">
          </div>
        </div>

        <div class="admin-form-section-title">3. DATES, STATUS & DOWNLOAD LINKS</div>
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 1rem;">
          <div class="admin-form-group">
            <label class="admin-form-label">Event Date *</label>
            <input type="date" name="event_date" value="<?= date('Y-m-d') ?>" required class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Live Status *</label>
            <select name="status" class="admin-form-control" required>
              <option value="RELEASED">✓ Available Now</option>
              <option value="EXPECTED">⏳ Releasing Soon</option>
              <option value="CITY_SLIP">🗺️ Exam City Intimation Slip</option>
              <option value="POSTPONED">⚠️ Postponed / Delayed</option>
            </select>
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Is Date Tentative?</label>
            <select name="is_tentative" class="admin-form-control">
              <option value="0">No (Confirmed Release)</option>
              <option value="1">Yes (Tentative / Expected)</option>
            </select>
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Download Portal Link</label>
            <input type="url" name="reference_url" placeholder="https://ssc.gov.in" class="admin-form-control">
          </div>

          <div class="admin-form-group" style="grid-column: 1 / -1;">
            <label class="admin-form-label">Details / Instructions for Aspirants</label>
            <textarea name="details" rows="2" placeholder="e.g. Candidates must carry printed copy of e-Admit Card along with original photo ID proof to the exam hall." class="admin-form-control"></textarea>
          </div>
        </div>

      </div>

      <div class="admin-modal-footer">
        <button type="button" class="admin-btn admin-btn-glass" onclick="closeModal('addEventModal')">Cancel</button>
        <button type="submit" id="saveEventBtn" class="admin-btn admin-btn-primary"><?= admin_icon('check', '', 14) ?> Publish Admit Card Notice</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Event Modal -->
<div id="editEventModal" class="admin-modal-overlay">
  <div class="admin-modal-card">
    <div class="admin-modal-header">
      <h3 class="admin-modal-title"><?= admin_icon('edit', '', 18) ?> Edit Admit Card / Exam Notice</h3>
      <button class="admin-modal-close-btn" onclick="closeModal('editEventModal')">&times;</button>
    </div>

    <form id="editEventForm">
      <input type="hidden" name="id" id="edit_event_id">

      <div class="admin-modal-body">
        
        <div class="admin-form-section-title">1. NOTICE DETAILS</div>
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
          <div class="admin-form-group">
            <label class="admin-form-label">Event / Notice Title *</label>
            <input type="text" name="event_title" id="edit_event_title" required class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Notice Type *</label>
            <select name="event_type" id="edit_event_type" required class="admin-form-control">
              <option value="ADMIT_CARD_RELEASED">ADMIT_CARD_RELEASED</option>
              <option value="EXAM_DATE">📅 EXAM_DATE</option>
              <option value="CORRECTION_WINDOW_OPENED"> CORRECTION_WINDOW_OPENED</option>
            </select>
          </div>
        </div>

        <div class="admin-form-section-title">2. RECRUITMENT & ORGANIZATION</div>
        <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 1rem;">
          <div class="admin-form-group">
            <label class="admin-form-label">Linked Recruitment</label>
            <select name="recruitment_id" id="edit_event_rec_id" class="admin-form-control">
              <option value="">-- Standalone / Unlinked Entry --</option>
              <?php foreach ($recruitmentsList as $r): ?>
                <option value="<?= $r['id'] ?>">
                  #<?= $r['id'] ?> - <?= htmlspecialchars($r['organization_name']) ?>: <?= htmlspecialchars($r['title']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Organization Name</label>
            <input type="text" name="organization_name" id="edit_event_org_name" class="admin-form-control">
          </div>
        </div>

        <div class="admin-form-section-title">3. DATES, STATUS & LINKS</div>
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 1rem;">
          <div class="admin-form-group">
            <label class="admin-form-label">Event Date</label>
            <input type="date" name="event_date" id="edit_event_date" required class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Live Status</label>
            <select name="status" id="edit_event_status" class="admin-form-control">
              <option value="RELEASED">✓ Available Now</option>
              <option value="EXPECTED">⏳ Releasing Soon</option>
              <option value="CITY_SLIP">🗺️ Exam City Intimation Slip</option>
              <option value="POSTPONED">⚠️ Postponed / Delayed</option>
            </select>
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Is Date Tentative?</label>
            <select name="is_tentative" id="edit_event_is_tentative" class="admin-form-control">
              <option value="0">No (Confirmed)</option>
              <option value="1">Yes (Tentative)</option>
            </select>
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Portal Download Link</label>
            <input type="url" name="reference_url" id="edit_event_reference_url" class="admin-form-control">
          </div>

          <div class="admin-form-group" style="grid-column: 1 / -1;">
            <label class="admin-form-label">Details / Instructions</label>
            <textarea name="details" id="edit_event_details" rows="2" class="admin-form-control"></textarea>
          </div>
        </div>

      </div>

      <div class="admin-modal-footer">
        <button type="button" class="admin-btn admin-btn-glass" onclick="closeModal('editEventModal')">Cancel</button>
        <button type="submit" id="updateEventBtn" class="admin-btn admin-btn-primary">💾 Update Notice</button>
      </div>
    </form>
  </div>
</div>

<script>
function openModal(id) {
  const el = document.getElementById(id);
  if (el) el.classList.add('active');
}

function closeModal(id) {
  const el = document.getElementById(id);
  if (el) el.classList.remove('active');
}

function autoFillOrg(sel) {
  const opt = sel.options[sel.selectedIndex];
  const org = opt.getAttribute('data-org');
  if (org) {
    document.getElementById('add_event_org_name').value = org;
  }
}

// Add Event Handler
document.getElementById('addEventForm')?.addEventListener('submit', async function(e) {
  e.preventDefault();
  const btn = document.getElementById('saveEventBtn');
  btn.disabled = true;
  btn.innerText = 'Publishing...';

  const formData = new FormData(this);
  const payload = Object.fromEntries(formData.entries());

  try {
    const res = await fetch('/api/v1/admin/events/create', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json();

    if (data.success) {
      alert('✓ ' + data.message);
      window.location.reload();
    } else {
      alert('❌ Error: ' + (data.error || 'Failed to publish notice'));
    }
  } catch (err) {
    alert('❌ Connection failed: ' + err.message);
  } finally {
    btn.disabled = false;
    btn.innerText = '💾 Publish Admit Card Notice';
  }
});

// Edit Event Fetch & Populate
async function editEvent(id) {
  try {
    const res = await fetch(`/api/v1/admin/events/get?id=${id}`);
    const data = await res.json();

    if (!data.success || !data.data) {
      alert('❌ Could not load event data.');
      return;
    }

    const ev = data.data;
    document.getElementById('edit_event_id').value = ev.id;
    document.getElementById('edit_event_title').value = ev.event_title || '';
    document.getElementById('edit_event_type').value = ev.event_type || 'ADMIT_CARD_RELEASED';
    document.getElementById('edit_event_status').value = ev.status || 'RELEASED';
    document.getElementById('edit_event_rec_id').value = ev.recruitment_id || '';
    document.getElementById('edit_event_org_name').value = ev.organization_name || ev.rec_org_name || '';
    document.getElementById('edit_event_date').value = ev.event_date || '';
    document.getElementById('edit_event_is_tentative').value = ev.is_tentative ? '1' : '0';
    document.getElementById('edit_event_reference_url').value = ev.reference_url || '';
    document.getElementById('edit_event_details').value = ev.details || '';

    openModal('editEventModal');
  } catch (err) {
    alert('❌ Error fetching details: ' + err.message);
  }
}

// Update Event Handler
document.getElementById('editEventForm')?.addEventListener('submit', async function(e) {
  e.preventDefault();
  const btn = document.getElementById('updateEventBtn');
  btn.disabled = true;
  btn.innerText = 'Updating...';

  const formData = new FormData(this);
  const payload = Object.fromEntries(formData.entries());

  try {
    const res = await fetch('/api/v1/admin/events/update', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json();

    if (data.success) {
      alert('✓ ' + data.message);
      window.location.reload();
    } else {
      alert('❌ Error: ' + (data.error || 'Failed to update notice'));
    }
  } catch (err) {
    alert('❌ Connection failed: ' + err.message);
  } finally {
    btn.disabled = false;
    btn.innerText = '💾 Update Notice';
  }
});

// Delete Event
async function deleteEvent(id) {
  if (!confirm(`Are you sure you want to delete Event Notice #${id}? This action cannot be undone.`)) {
    return;
  }

  try {
    const res = await fetch('/api/v1/admin/events/delete', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: id })
    });
    const data = await res.json();

    if (data.success) {
      const row = document.getElementById(`event-row-${id}`);
      if (row) {
        row.style.opacity = '0';
        setTimeout(() => row.remove(), 250);
      }
      alert(data.message || 'Notice deleted successfully!');
    } else {
      alert('Error: ' + (data.error || 'Failed to delete event'));
    }
  } catch (err) {
    alert('Connection failed: ' + err.message);
  }
}

// Live Inline Status Auto-Save for Admit Cards Table
document.querySelectorAll('.event-status-select').forEach(select => {
  select.addEventListener('change', async () => {
    const eventId = select.getAttribute('data-event-id');
    const status = select.value;
    select.disabled = true;
    try {
      const res = await fetch('/api/v1/admin/events/update-status', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: eventId, status: status })
      });
      const data = await res.json();
      if (data.success) {
        select.style.borderColor = '#059669';
        select.style.background = '#ecfdf5';
        setTimeout(() => {
          select.style.borderColor = '';
          select.style.background = '';
        }, 1500);
      } else {
        alert('❌ ' + (data.error || 'Failed to update status.'));
      }
    } catch (err) {
      alert('❌ Network error while updating event status.');
    } finally {
      select.disabled = false;
    }
  });
});
</script>

<?php require_once __DIR__ . '/partials/admin_layout_bottom.php'; ?>
