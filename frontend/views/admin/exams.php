<?php
require_once __DIR__ . '/../../../backend/app/Database.php';
use App\Database;

$db = Database::getConnection();

$exams = $db->query("SELECT * FROM exams ORDER BY category ASC, name ASC")->fetchAll();

$pageTitle = "Manage Exam Hubs — Admin Control Center";
$adminPageTitle = "Exam Hubs";
$adminPageHeading = "Autonomous Examination Hubs & Pattern Directory";
require_once __DIR__ . '/partials/admin_icons.php';
$adminHeaderActionHtml = '<button onclick="openModal(\'addExamModal\')" class="admin-btn admin-btn-primary admin-btn-sm">' . admin_icon('plus', '', 14) . ' Create Exam Hub</button>';

require_once __DIR__ . '/partials/admin_layout_top.php';
?>

<!-- Exam Hubs Directory Card -->
<div class="admin-card">
  <div class="admin-card-header">
    <div class="admin-card-title-wrap">
      <h3 class="admin-card-title"><?= admin_icon('graduation-cap', '', 18) ?> Registered Examination Hubs</h3>
      <p class="admin-card-desc">Showing <strong><?= count($exams) ?></strong> national and state recruitment test hubs with structured patterns and cutoffs</p>
    </div>
    <button onclick="openModal('addExamModal')" class="admin-btn admin-btn-primary admin-btn-sm">
      <?= admin_icon('plus', '', 14) ?> Create Exam Hub
    </button>
  </div>

  <div class="admin-table-responsive">
    <table class="admin-table">
      <thead>
        <tr>
          <th style="width: 70px;">ID</th>
          <th>Exam Name / Short Code</th>
          <th>Conducting Body</th>
          <th>Category</th>
          <th>Frequency</th>
          <th>Status</th>
          <th style="text-align: right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($exams)): ?>
          <tr>
            <td colspan="7" style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
              <div style="font-size: 2rem; margin-bottom: 0.5rem;"></div>
              <strong>No exam hubs registered yet.</strong>
              <p style="font-size: 0.8rem; margin-top: 0.35rem;">Click "Create Exam Hub" above to register your first examination.</p>
            </td>
          </tr>
        <?php endif; ?>

        <?php foreach ($exams as $ex): ?>
          <tr id="exam-row-<?= $ex['id'] ?>">
            <td>
              <span class="admin-id-badge">#<?= $ex['id'] ?></span>
            </td>
            <td>
              <div style="font-weight: 700; color: var(--text-dark); font-size: 0.95rem;">
                <?= htmlspecialchars($ex['name']) ?>
              </div>
              <div style="font-size: 0.75rem; color: var(--primary-ruby); font-weight: 800; margin-top: 0.15rem;">
                CODE: <?= htmlspecialchars($ex['short_name']) ?>
              </div>
            </td>
            <td style="font-weight: 600; color: var(--text-body);">
              <?= htmlspecialchars($ex['conducting_body']) ?>
            </td>
            <td>
              <span class="admin-badge badge-org" style="font-size: 0.68rem;">
                <?= htmlspecialchars($ex['category']) ?>
              </span>
            </td>
            <td style="font-size: 0.85rem; color: var(--text-muted);">
              <?= htmlspecialchars($ex['frequency'] ?: 'Annual') ?>
            </td>
            <td>
              <span class="admin-badge <?= $ex['is_active'] ? 'badge-active' : 'badge-org' ?>">
                <?= $ex['is_active'] ? '✓ Live Hub' : 'Inactive' ?>
              </span>
            </td>
            <td style="text-align: right;">
              <div class="admin-action-btn-group" style="justify-content: flex-end;">
                <a href="/exams/<?= htmlspecialchars($ex['slug']) ?>" target="_blank" class="admin-btn admin-btn-glass admin-btn-icon-only" title="View Public Exam Hub">
                  <?= admin_icon('eye', '', 15) ?>
                </a>
                <button onclick="editExam(<?= $ex['id'] ?>)" class="admin-btn admin-btn-glass admin-btn-icon-only" title="Edit Exam Hub">
                  <?= admin_icon('edit', '', 15) ?>
                </button>
                <button onclick="deleteExam(<?= $ex['id'] ?>)" class="admin-btn admin-btn-danger admin-btn-icon-only" title="Delete Exam Hub">
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

<!-- Add Exam Hub Modal -->
<div id="addExamModal" class="admin-modal-overlay">
  <div class="admin-modal-card">
    <div class="admin-modal-header">
      <h3 class="admin-modal-title"><?= admin_icon('plus', '', 18) ?> Create New Exam Intelligence Hub</h3>
      <button class="admin-modal-close-btn" onclick="closeModal('addExamModal')">&times;</button>
    </div>

    <form id="addExamForm">
      <div class="admin-modal-body">
        
        <div class="admin-form-section-title">1. BASIC EXAMINATION INFO</div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <div class="admin-form-group" style="grid-column: 1 / -1;">
            <label class="admin-form-label">Full Examination Name *</label>
            <input type="text" name="name" required placeholder="e.g. SSC Combined Graduate Level (CGL) Examination" class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Short Name / Code *</label>
            <input type="text" name="short_name" required placeholder="e.g. SSC CGL" class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Conducting Commission Body *</label>
            <input type="text" name="conducting_body" required placeholder="e.g. Staff Selection Commission (SSC)" class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Category</label>
            <select name="category" class="admin-form-control">
              <option value="Staff Selection">Staff Selection</option>
              <option value="Civil Services">Civil Services</option>
              <option value="Railways">Railways</option>
              <option value="Banking">Banking</option>
              <option value="Defense">Defense</option>
              <option value="State PSC">State PSC</option>
              <option value="Police">Police</option>
              <option value="Teaching">Teaching</option>
              <option value="Engineering">Engineering</option>
              <option value="Other">Other</option>
            </select>
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Official Portal Website</label>
            <input type="url" name="official_website" placeholder="https://ssc.gov.in" class="admin-form-control">
          </div>
        </div>

        <div class="admin-form-section-title">2. ELIGIBILITY & OVERVIEW DETAILS</div>
        <div style="display: grid; grid-template-columns: 1fr; gap: 1rem;">
          <div class="admin-form-group">
            <label class="admin-form-label">Exam Overview & Structure</label>
            <textarea name="overview" rows="3" placeholder="Comprehensive overview of exam stages, tiers, and selection process..." class="admin-form-control"></textarea>
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Educational Eligibility Summary</label>
            <input type="text" name="eligibility_summary" placeholder="e.g. Bachelor's Degree in any discipline from a recognized university" class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Age Limit & Relaxation Criteria</label>
            <input type="text" name="age_limit_summary" placeholder="e.g. 18 to 32 Years with standard category relaxations" class="admin-form-control">
          </div>
        </div>

      </div>

      <div class="admin-modal-footer">
        <button type="button" onclick="closeModal('addExamModal')" class="admin-btn admin-btn-glass admin-btn-sm">Cancel</button>
        <button type="submit" id="addExamSubmitBtn" class="admin-btn admin-btn-primary admin-btn-sm">Create Exam Hub</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Exam Hub Modal -->
<div id="editExamModal" class="admin-modal-overlay">
  <div class="admin-modal-card">
    <div class="admin-modal-header">
      <h3 class="admin-modal-title"><?= admin_icon('edit', '', 18) ?> Edit Examination Hub</h3>
      <button class="admin-modal-close-btn" onclick="closeModal('editExamModal')">&times;</button>
    </div>

    <form id="editExamForm">
      <input type="hidden" name="id" id="editExamId">
      <div class="admin-modal-body">
        
        <div class="admin-form-section-title">1. BASIC EXAMINATION INFO</div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <div class="admin-form-group" style="grid-column: 1 / -1;">
            <label class="admin-form-label">Full Examination Name *</label>
            <input type="text" name="name" id="editExamName" required class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Short Name / Code *</label>
            <input type="text" name="short_name" id="editExamShortName" required class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Conducting Commission Body *</label>
            <input type="text" name="conducting_body" id="editExamBody" required class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Category</label>
            <select name="category" id="editExamCategory" class="admin-form-control">
              <option value="Staff Selection">Staff Selection</option>
              <option value="Civil Services">Civil Services</option>
              <option value="Railways">Railways</option>
              <option value="Banking">Banking</option>
              <option value="Defense">Defense</option>
              <option value="State PSC">State PSC</option>
              <option value="Police">Police</option>
              <option value="Teaching">Teaching</option>
              <option value="Engineering">Engineering</option>
              <option value="Other">Other</option>
            </select>
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Exam Frequency</label>
            <input type="text" name="frequency" id="editExamFrequency" placeholder="e.g. Annual / Bi-annual" class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Official Portal Website</label>
            <input type="url" name="official_website" id="editExamWebsite" class="admin-form-control">
          </div>
        </div>

        <div class="admin-form-section-title">2. ELIGIBILITY & OVERVIEW DETAILS</div>
        <div style="display: grid; grid-template-columns: 1fr; gap: 1rem;">
          <div class="admin-form-group">
            <label class="admin-form-label">Exam Overview & Structure</label>
            <textarea name="overview" id="editExamOverview" rows="3" class="admin-form-control"></textarea>
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Educational Eligibility Summary</label>
            <input type="text" name="eligibility_summary" id="editExamEligibility" class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Age Limit & Relaxation Criteria</label>
            <input type="text" name="age_limit_summary" id="editExamAgeLimit" class="admin-form-control">
          </div>
        </div>

      </div>

      <div class="admin-modal-footer">
        <button type="button" onclick="closeModal('editExamModal')" class="admin-btn admin-btn-glass admin-btn-sm">Cancel</button>
        <button type="submit" id="editExamSubmitBtn" class="admin-btn admin-btn-primary admin-btn-sm">Update Exam Hub</button>
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

// 1. Add Exam Form AJAX
document.getElementById('addExamForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const submitBtn = document.getElementById('addExamSubmitBtn');
  submitBtn.disabled = true;
  submitBtn.innerText = 'Creating Hub...';

  const formData = new FormData(e.target);
  const data = Object.fromEntries(formData.entries());

  try {
    const res = await fetch('/api/v1/admin/exams/create', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    const result = await res.json();
    if (result.success) {
      alert(result.message || 'Exam Hub created successfully!');
      closeModal('addExamModal');
      window.location.reload();
    } else {
      alert(result.error || 'Failed to create Exam Hub.');
    }
  } catch (err) {
    alert('Network error while saving Exam Hub.');
  } finally {
    submitBtn.disabled = false;
    submitBtn.innerText = 'Create Exam Hub';
  }
});

// 2. Fetch Exam Details for Editing
async function editExam(id) {
  try {
    const res = await fetch(`/api/v1/admin/exams/get?id=${id}`);
    const result = await res.json();
    if (!result.success || !result.data) {
      alert(result.error || 'Could not fetch exam details.');
      return;
    }

    const d = result.data;
    document.getElementById('editExamId').value = d.id;
    document.getElementById('editExamName').value = d.name || '';
    document.getElementById('editExamShortName').value = d.short_name || '';
    document.getElementById('editExamBody').value = d.conducting_body || '';
    document.getElementById('editExamCategory').value = d.category || 'Other';
    document.getElementById('editExamFrequency').value = d.frequency || 'Annual';
    document.getElementById('editExamWebsite').value = d.official_website || '';
    document.getElementById('editExamOverview').value = d.overview || '';
    document.getElementById('editExamEligibility').value = d.eligibility_summary || '';
    document.getElementById('editExamAgeLimit').value = d.age_limit_summary || '';

    openModal('editExamModal');
  } catch (err) {
    alert('Failed to load exam details from server.');
  }
}

// 3. Edit Exam Form AJAX Submission
document.getElementById('editExamForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const submitBtn = document.getElementById('editExamSubmitBtn');
  submitBtn.disabled = true;
  submitBtn.innerText = 'Updating Hub...';

  const formData = new FormData(e.target);
  const data = Object.fromEntries(formData.entries());

  try {
    const res = await fetch('/api/v1/admin/exams/update', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    const result = await res.json();
    if (result.success) {
      alert(result.message || 'Exam Hub updated successfully!');
      closeModal('editExamModal');
      window.location.reload();
    } else {
      alert(result.error || 'Failed to update Exam Hub.');
    }
  } catch (err) {
    alert('Network error while updating Exam Hub.');
  } finally {
    submitBtn.disabled = false;
    submitBtn.innerText = 'Update Exam Hub';
  }
});

// 4. Delete Exam Action
async function deleteExam(id) {
  if (!confirm(`Are you sure you want to delete Exam Hub #${id}? This will remove it from the live portal.`)) return;
  try {
    const res = await fetch('/api/v1/admin/exams/delete', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id })
    });
    const result = await res.json();
    if (result.success) {
      const row = document.getElementById(`exam-row-${id}`);
      if (row) {
        row.style.opacity = '0';
        setTimeout(() => row.remove(), 250);
      }
    } else {
      alert(result.error || 'Failed to delete Exam Hub.');
    }
  } catch (err) {
    alert('Network error while deleting Exam Hub.');
  }
}
</script>

<?php require_once __DIR__ . '/partials/admin_layout_bottom.php'; ?>
