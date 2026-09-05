<?php
require_once __DIR__ . '/../../../backend/app/Database.php';
use App\Database;

$db = Database::getConnection();

$commissions = $db->query("SELECT * FROM commissions ORDER BY category ASC, name ASC")->fetchAll();

// Count active recruitments per commission
foreach ($commissions as $idx => $c) {
    $filterKw = !empty($c['filter_keyword']) ? $c['filter_keyword'] : $c['short_name'];
    $stmt = $db->prepare("SELECT COUNT(*) as c FROM recruitments WHERE (organization_name LIKE ? OR title LIKE ?) AND status = 'Active'");
    $stmt->execute(["%{$filterKw}%", "%{$filterKw}%"]);
    $commissions[$idx]['active_notices'] = $stmt->fetch()['c'] ?? 0;
}

$pageTitle = "Manage Commissions — Admin Control Center";
$adminPageTitle = "Commissions";
$adminPageHeading = "Government Recruiting Commissions & Constitutional Bodies Directory";
require_once __DIR__ . '/partials/admin_icons.php';
$adminHeaderActionHtml = '<button onclick="openModal(\'addCommissionModal\')" class="admin-btn admin-btn-primary admin-btn-sm">' . admin_icon('plus', '', 14) . ' Create Commission</button>';

require_once __DIR__ . '/partials/admin_layout_top.php';
?>

<!-- Top Statistics KPI Cards -->
<div class="admin-kpi-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); margin-bottom: 1.75rem;">
  <div class="admin-kpi-card">
    <div class="admin-kpi-header">
      <span class="admin-kpi-label">Total Commissions</span>
      <div class="admin-kpi-icon ruby"><?= admin_icon('landmark', '', 18) ?></div>
    </div>
    <div class="admin-kpi-value" style="color: var(--primary-ruby);"><?= number_format(count($commissions)) ?></div>
    <div class="admin-kpi-subtext">
      Constitutional & Autonomous Authorities
    </div>
  </div>

  <div class="admin-kpi-card">
    <div class="admin-kpi-header">
      <span class="admin-kpi-label">Active Directories</span>
      <div class="admin-kpi-icon emerald"><?= admin_icon('check-circle', '', 18) ?></div>
    </div>
    <div class="admin-kpi-value" style="color: var(--color-emerald);"><?= number_format(count(array_filter($commissions, fn($c) => !empty($c['is_active'])))) ?></div>
    <div class="admin-kpi-subtext">
      <span style="color: var(--color-emerald); font-weight: 700;">● Live</span> Public Dossiers & Archives
    </div>
  </div>

  <div class="admin-kpi-card">
    <div class="admin-kpi-header">
      <span class="admin-kpi-label">Total Linked Notices</span>
      <div class="admin-kpi-icon blue"><?= admin_icon('file-text', '', 18) ?></div>
    </div>
    <div class="admin-kpi-value" style="color: var(--color-blue);"><?= number_format(array_sum(array_column($commissions, 'active_notices'))) ?></div>
    <div class="admin-kpi-subtext">
      Active Government Gazette Openings
    </div>
  </div>
</div>

<!-- Commissions Directory Card -->
<div class="admin-card">
  <div class="admin-card-header">
    <div class="admin-card-title-wrap">
      <h3 class="admin-card-title"><?= admin_icon('landmark', '', 18) ?> Government Recruiting Commissions</h3>
      <p class="admin-card-desc">Showing <strong><?= count($commissions) ?></strong> recruiting authorities with public dossiers and recruitment matrices</p>
    </div>
    <button onclick="openModal('addCommissionModal')" class="admin-btn admin-btn-primary admin-btn-sm">
      <?= admin_icon('plus', '', 14) ?> Create Commission
    </button>
  </div>

  <div class="admin-table-responsive">
    <table class="admin-table">
      <thead>
        <tr>
          <th style="width: 60px;">ID</th>
          <th>Commission / Authority</th>
          <th>Short Code</th>
          <th>Category</th>
          <th>Headquarters</th>
          <th>Live Notices</th>
          <th>Status</th>
          <th style="text-align: right;">Actions</th>
        </tr>
      </thead>
      <tbody id="commissionsTableBody">
        <?php if (empty($commissions)): ?>
          <tr id="emptyRow">
            <td colspan="8" style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
              <div style="font-size: 2rem; margin-bottom: 0.5rem;"></div>
              <strong>No recruiting commissions registered yet.</strong>
              <p style="font-size: 0.8rem; margin-top: 0.35rem;">Click "Create Commission" above to register your first body.</p>
            </td>
          </tr>
        <?php endif; ?>

        <?php foreach ($commissions as $comm): ?>
          <tr id="commission-row-<?= $comm['id'] ?>">
            <td>
              <span class="admin-id-badge">#<?= $comm['id'] ?></span>
            </td>
            <td>
              <div style="display: flex; align-items: center; gap: 0.65rem;">
                <div style="font-size: 1.4rem; line-height: 1;"><?= $comm['emblem'] ?: '' ?></div>
                <div>
                  <div style="font-weight: 700; color: var(--text-dark); font-size: 0.95rem;">
                    <?= htmlspecialchars($comm['name']) ?>
                  </div>
                  <?php if (!empty($comm['website'])): ?>
                    <a href="<?= htmlspecialchars($comm['website']) ?>" target="_blank" style="font-size: 0.75rem; color: var(--primary-ruby); text-decoration: none;">
                      <?= htmlspecialchars(parse_url($comm['website'], PHP_URL_HOST) ?: $comm['website']) ?> ↗
                    </a>
                  <?php endif; ?>
                </div>
              </div>
            </td>
            <td>
              <span class="admin-badge badge-org" style="font-size: 0.72rem; font-weight: 800;">
                <?= htmlspecialchars($comm['short_name']) ?>
              </span>
            </td>
            <td style="font-size: 0.85rem; color: var(--text-body);">
              <?= htmlspecialchars($comm['category']) ?>
            </td>
            <td style="font-size: 0.825rem; color: var(--text-muted); max-width: 200px;">
              <?= htmlspecialchars($comm['hq'] ?: 'New Delhi') ?>
            </td>
            <td>
              <span class="admin-badge <?= $comm['active_notices'] > 0 ? 'badge-active' : 'badge-org' ?>">
                <?= $comm['active_notices'] ?> Active
              </span>
            </td>
            <td>
              <span class="admin-badge <?= $comm['is_active'] ? 'badge-active' : 'badge-org' ?>">
                <?= $comm['is_active'] ? '✓ Live' : 'Inactive' ?>
              </span>
            </td>
            <td style="text-align: right;">
              <div class="admin-action-btn-group" style="justify-content: flex-end;">
                <a href="/commissions/<?= htmlspecialchars($comm['slug']) ?>" target="_blank" class="admin-btn admin-btn-glass admin-btn-icon-only" title="View Public Commission Dossier">
                  <?= admin_icon('eye', '', 15) ?>
                </a>
                <button onclick="editCommission(<?= $comm['id'] ?>)" class="admin-btn admin-btn-glass admin-btn-icon-only" title="Edit Commission">
                  <?= admin_icon('edit', '', 15) ?>
                </button>
                <button onclick="deleteCommission(<?= $comm['id'] ?>)" class="admin-btn admin-btn-danger admin-btn-icon-only" title="Delete Commission">
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

<!-- Add Commission Modal -->
<div id="addCommissionModal" class="admin-modal-overlay">
  <div class="admin-modal-card">
    <div class="admin-modal-header">
      <h3 class="admin-modal-title"><?= admin_icon('plus', '', 18) ?> Register New Recruiting Commission</h3>
      <button class="admin-modal-close-btn" onclick="closeModal('addCommissionModal')">&times;</button>
    </div>

    <form id="addCommissionForm">
      <div class="admin-modal-body">
        
        <div class="admin-form-section-title">1. BASIC IDENTIFICATION</div>
        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 1rem;">
          <div class="admin-form-group">
            <label class="admin-form-label">Full Official Commission Name *</label>
            <input type="text" name="name" id="add_comm_name" required placeholder="e.g. Union Public Service Commission (UPSC)" class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Short Acronym *</label>
            <input type="text" name="short_name" id="add_comm_short_name" required placeholder="e.g. UPSC" class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Vector Icon</label>
            <select name="emblem" class="admin-form-control">
              <option value="landmark">Landmark / Constitutional</option>
              <option value="building-2">Building 2 / Staff Selection</option>
              <option value="train">Train / Railway Board</option>
              <option value="bank">Bank / IBPS & SBI</option>
              <option value="plane">Plane / Defence & Air Force</option>
              <option value="building">Building / State PSC</option>
              <option value="shield">Shield / Police & Security</option>
              <option value="briefcase">Briefcase / PSU & Autonomous</option>
            </select>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
          <div class="admin-form-group">
            <label class="admin-form-label">URL Slug (Auto or Custom)</label>
            <input type="text" name="slug" id="add_comm_slug" placeholder="e.g. upsc" class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Category / Jurisdiction</label>
            <input type="text" name="category" placeholder="e.g. Central Constitutional Recruiting Commission" class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Search Filter Keyword</label>
            <input type="text" name="filter_keyword" id="add_comm_filter_keyword" placeholder="e.g. UPSC (matches recruitment titles)" class="admin-form-control">
          </div>
        </div>

        <div class="admin-form-section-title">2. PORTALS & CONTACT INFO</div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <div class="admin-form-group">
            <label class="admin-form-label">Official Portal URL</label>
            <input type="url" name="website" placeholder="https://upsc.gov.in" class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">OTR / One-Time Registration Portal</label>
            <input type="url" name="otr_url" placeholder="https://upsconline.nic.in" class="admin-form-control">
          </div>

          <div class="admin-form-group" style="grid-column: 1 / -1;">
            <label class="admin-form-label">Headquarters Address</label>
            <input type="text" name="hq" placeholder="Dholpur House, Shahjahan Road, New Delhi - 110069" class="admin-form-control">
          </div>
        </div>

        <div class="admin-form-section-title">3. CANDIDATE INTELLIGENCE & DOSSIER</div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <div class="admin-form-group">
            <label class="admin-form-label">Annual Candidate Footprint</label>
            <input type="text" name="annual_candidates" placeholder="e.g. 1.5 Million+ Aspirants" class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Standard Selection Workflow</label>
            <input type="text" name="selection_phases" placeholder="Prelims -> Mains -> Interview" class="admin-form-control">
          </div>

          <div class="admin-form-group" style="grid-column: 1 / -1;">
            <label class="admin-form-label">Overview & Constitutional Mandate</label>
            <textarea name="description" rows="3" placeholder="Detailed mandate and background of this recruiting authority..." class="admin-form-control"></textarea>
          </div>
        </div>

      </div>

      <div class="admin-modal-footer">
        <button type="button" class="admin-btn admin-btn-glass" onclick="closeModal('addCommissionModal')">Cancel</button>
        <button type="submit" id="saveCommissionBtn" class="admin-btn admin-btn-primary"><?= admin_icon('check', '', 14) ?> Save & Publish Commission</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Commission Modal -->
<div id="editCommissionModal" class="admin-modal-overlay">
  <div class="admin-modal-card">
    <div class="admin-modal-header">
      <h3 class="admin-modal-title"><?= admin_icon('edit', '', 18) ?> Edit Commission Information</h3>
      <button class="admin-modal-close-btn" onclick="closeModal('editCommissionModal')">&times;</button>
    </div>

    <form id="editCommissionForm">
      <input type="hidden" name="id" id="edit_comm_id">
      
      <div class="admin-modal-body">
        
        <div class="admin-form-section-title">1. BASIC IDENTIFICATION</div>
        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 1rem;">
          <div class="admin-form-group">
            <label class="admin-form-label">Full Official Commission Name *</label>
            <input type="text" name="name" id="edit_comm_name" required class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Short Acronym *</label>
            <input type="text" name="short_name" id="edit_comm_short_name" required class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Vector Icon</label>
            <select name="emblem" id="edit_comm_emblem" class="admin-form-control">
              <option value="landmark">Landmark / Constitutional</option>
              <option value="building-2">Building 2 / Staff Selection</option>
              <option value="train">Train / Railway Board</option>
              <option value="bank">Bank / IBPS & SBI</option>
              <option value="plane">Plane / Defence & Air Force</option>
              <option value="building">Building / State PSC</option>
              <option value="shield">Shield / Police & Security</option>
              <option value="briefcase">Briefcase / PSU & Autonomous</option>
            </select>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
          <div class="admin-form-group">
            <label class="admin-form-label">URL Slug</label>
            <input type="text" name="slug" id="edit_comm_slug" class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Category</label>
            <input type="text" name="category" id="edit_comm_category" class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Search Filter Keyword</label>
            <input type="text" name="filter_keyword" id="edit_comm_filter_keyword" class="admin-form-control">
          </div>
        </div>

        <div class="admin-form-section-title">2. PORTALS & CONTACT INFO</div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <div class="admin-form-group">
            <label class="admin-form-label">Official Website</label>
            <input type="url" name="website" id="edit_comm_website" class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">OTR URL</label>
            <input type="url" name="otr_url" id="edit_comm_otr_url" class="admin-form-control">
          </div>

          <div class="admin-form-group" style="grid-column: 1 / -1;">
            <label class="admin-form-label">Headquarters Address</label>
            <input type="text" name="hq" id="edit_comm_hq" class="admin-form-control">
          </div>
        </div>

        <div class="admin-form-section-title">3. CANDIDATE INTELLIGENCE</div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <div class="admin-form-group">
            <label class="admin-form-label">Annual Candidate Footprint</label>
            <input type="text" name="annual_candidates" id="edit_comm_annual_candidates" class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Selection Workflow</label>
            <input type="text" name="selection_phases" id="edit_comm_selection_phases" class="admin-form-control">
          </div>

          <div class="admin-form-group" style="grid-column: 1 / -1;">
            <label class="admin-form-label">Overview & Mandate</label>
            <textarea name="description" id="edit_comm_description" rows="3" class="admin-form-control"></textarea>
          </div>
        </div>

      </div>

      <div class="admin-modal-footer">
        <button type="button" class="admin-btn admin-btn-glass" onclick="closeModal('editCommissionModal')">Cancel</button>
        <button type="submit" id="updateCommissionBtn" class="admin-btn admin-btn-primary">💾 Update Commission</button>
      </div>
    </form>
  </div>
</div>

<script>
// Modal Helpers
function openModal(id) {
  const el = document.getElementById(id);
  if (el) el.classList.add('active');
}

function closeModal(id) {
  const el = document.getElementById(id);
  if (el) el.classList.remove('active');
}

// Live Slug and Filter Keyword Generator for Add Commission
document.getElementById('add_comm_short_name')?.addEventListener('input', function() {
  const shortName = this.value.trim();
  const slugInput = document.getElementById('add_comm_slug');
  const kwInput = document.getElementById('add_comm_filter_keyword');
  
  if (slugInput && !slugInput.dataset.touched) {
    slugInput.value = shortName.toLowerCase().replace(/[^a-z0-9-]+/g, '-').replace(/^-+|-+$/g, '');
  }
  if (kwInput && !kwInput.dataset.touched) {
    kwInput.value = shortName;
  }
});

document.getElementById('add_comm_slug')?.addEventListener('input', function() {
  this.dataset.touched = 'true';
});

document.getElementById('add_comm_filter_keyword')?.addEventListener('input', function() {
  this.dataset.touched = 'true';
});

// Add Commission Form Handler
document.getElementById('addCommissionForm')?.addEventListener('submit', async function(e) {
  e.preventDefault();
  const btn = document.getElementById('saveCommissionBtn');
  btn.disabled = true;
  btn.innerText = 'Creating...';

  const formData = new FormData(this);
  const payload = Object.fromEntries(formData.entries());

  try {
    const res = await fetch('/api/v1/admin/commissions/create', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json();

    if (data.success) {
      alert('✓ ' + data.message);
      window.location.reload();
    } else {
      alert('❌ Error: ' + (data.error || 'Failed to create commission'));
    }
  } catch (err) {
    alert('❌ Connection failed: ' + err.message);
  } finally {
    btn.disabled = false;
    btn.innerText = '💾 Save & Publish Commission';
  }
});

// Edit Commission Fetch & Populate
async function editCommission(id) {
  try {
    const res = await fetch(`/api/v1/admin/commissions/get?id=${id}`);
    const data = await res.json();

    if (!data.success || !data.data) {
      alert('❌ Could not load commission data.');
      return;
    }

    const c = data.data;
    document.getElementById('edit_comm_id').value = c.id;
    document.getElementById('edit_comm_name').value = c.name || '';
    document.getElementById('edit_comm_short_name').value = c.short_name || '';
    document.getElementById('edit_comm_slug').value = c.slug || '';
    document.getElementById('edit_comm_emblem').value = c.emblem || '';
    document.getElementById('edit_comm_category').value = c.category || '';
    document.getElementById('edit_comm_filter_keyword').value = c.filter_keyword || '';
    document.getElementById('edit_comm_website').value = c.website || '';
    document.getElementById('edit_comm_otr_url').value = c.otr_url || '';
    document.getElementById('edit_comm_hq').value = c.hq || '';
    document.getElementById('edit_comm_annual_candidates').value = c.annual_candidates || '';
    document.getElementById('edit_comm_selection_phases').value = c.selection_phases || '';
    document.getElementById('edit_comm_description').value = c.description || '';

    openModal('editCommissionModal');
  } catch (err) {
    alert('❌ Error fetching details: ' + err.message);
  }
}

// Update Commission Form Handler
document.getElementById('editCommissionForm')?.addEventListener('submit', async function(e) {
  e.preventDefault();
  const btn = document.getElementById('updateCommissionBtn');
  btn.disabled = true;
  btn.innerText = 'Updating...';

  const formData = new FormData(this);
  const payload = Object.fromEntries(formData.entries());

  try {
    const res = await fetch('/api/v1/admin/commissions/update', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json();

    if (data.success) {
      alert('✓ ' + data.message);
      window.location.reload();
    } else {
      alert('❌ Error: ' + (data.error || 'Failed to update commission'));
    }
  } catch (err) {
    alert('❌ Connection failed: ' + err.message);
  } finally {
    btn.disabled = false;
    btn.innerText = '💾 Update Commission';
  }
});

// Delete Commission
async function deleteCommission(id) {
  if (!confirm(`Are you sure you want to delete Commission #${id}? This action cannot be undone.`)) {
    return;
  }

  try {
    const res = await fetch('/api/v1/admin/commissions/delete', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: id })
    });
    const data = await res.json();

    if (data.success) {
      const row = document.getElementById(`commission-row-${id}`);
      if (row) {
        row.style.opacity = '0';
        setTimeout(() => row.remove(), 250);
      }
      alert(data.message || 'Commission deleted successfully!');
    } else {
      alert('Error: ' + (data.error || 'Failed to delete commission'));
    }
  } catch (err) {
    alert('Connection failed: ' + err.message);
  }
}
</script>

<?php require_once __DIR__ . '/partials/admin_layout_bottom.php'; ?>
