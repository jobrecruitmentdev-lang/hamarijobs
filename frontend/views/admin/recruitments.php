<?php
require_once __DIR__ . '/../../../backend/app/Database.php';
use App\Database;

$db = Database::getConnection();

// Filter & Search Parameters
$search = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');
$status = trim($_GET['status'] ?? '');

$query = "SELECT * FROM recruitments WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (title LIKE ? OR organization_name LIKE ? OR advertisement_number LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if (!empty($category)) {
    $query .= " AND organization_name LIKE ?";
    $params[] = "%{$category}%";
}

if (!empty($status)) {
    $query .= " AND status = ?";
    $params[] = $status;
}

$query .= " ORDER BY updated_at DESC LIMIT 100";
$stmt = $db->prepare($query);
$stmt->execute($params);
$jobs = $stmt->fetchAll();

$pageTitle = "Manage Recruitment Notices — Admin Control Center";
$adminPageTitle = "Manage Jobs";
$adminPageHeading = "Recruitment Gazettes & Public Notices Directory";
$adminHeaderActionHtml = '<button onclick="openModal(\'addJobModal\')" class="admin-btn admin-btn-primary admin-btn-sm">➕ Add New Job Notice</button>';

require_once __DIR__ . '/partials/admin_layout_top.php';
?>

<!-- 1. Search & Filter Bar -->
<div class="admin-card" style="margin-bottom: 1.5rem; padding: 1.25rem 1.5rem;">
  <form method="GET" action="/admin/recruitments" style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: center; justify-content: space-between;">
    <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; flex: 1;">
      <div style="min-width: 240px; flex: 1;">
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="🔍 Search by Title, Commission, or Advt Ref No..." class="admin-form-control" style="font-size: 0.875rem;">
      </div>
      <select name="category" class="admin-form-control" style="width: auto; min-width: 160px; font-size: 0.875rem;">
        <option value="">All Commissions</option>
        <option value="SSC" <?= $category === 'SSC' ? 'selected' : '' ?>>Staff Selection (SSC)</option>
        <option value="UPSC" <?= $category === 'UPSC' ? 'selected' : '' ?>>UPSC</option>
        <option value="Railway" <?= $category === 'Railway' ? 'selected' : '' ?>>Railways (RRB)</option>
        <option value="IBPS" <?= $category === 'IBPS' ? 'selected' : '' ?>>Banking (IBPS/SBI)</option>
        <option value="Air Force" <?= $category === 'Air Force' ? 'selected' : '' ?>>Defence / IAF</option>
        <option value="GPSC" <?= $category === 'GPSC' ? 'selected' : '' ?>>State PSCs</option>
      </select>
      <select name="status" class="admin-form-control" style="width: auto; min-width: 140px; font-size: 0.875rem;">
        <option value="">All Statuses</option>
        <option value="Active" <?= $status === 'Active' ? 'selected' : '' ?>>🟢 Active</option>
        <option value="Upcoming" <?= $status === 'Upcoming' ? 'selected' : '' ?>>⏳ Upcoming</option>
        <option value="Exam_Phase" <?= $status === 'Exam_Phase' ? 'selected' : '' ?>>📝 Exam Phase</option>
        <option value="Result_Declared" <?= $status === 'Result_Declared' ? 'selected' : '' ?>>🏆 Result Out</option>
        <option value="Archived" <?= $status === 'Archived' ? 'selected' : '' ?>>📦 Archived</option>
      </select>
    </div>

    <div style="display: flex; gap: 0.5rem;">
      <button type="submit" class="admin-btn admin-btn-primary admin-btn-sm">
        Filter
      </button>
      <?php if (!empty($search) || !empty($category) || !empty($status)): ?>
        <a href="/admin/recruitments" class="admin-btn admin-btn-glass admin-btn-sm">
          Reset
        </a>
      <?php endif; ?>
    </div>
  </form>
</div>

<!-- 2. Recruitments Data Table -->
<div class="admin-card">
  <div class="admin-card-header">
    <div class="admin-card-title-wrap">
      <h3 class="admin-card-title">📑 Indexed Job Notifications</h3>
      <p class="admin-card-desc">Showing <strong><?= count($jobs) ?></strong> active government recruitment notices</p>
    </div>
    <div>
      <button onclick="openModal('addJobModal')" class="admin-btn admin-btn-primary admin-btn-sm">
        ➕ Add New Job Notice
      </button>
    </div>
  </div>

  <div class="admin-table-responsive">
    <table class="admin-table">
      <thead>
        <tr>
          <th style="width: 70px;">ID</th>
          <th>Commission / Title</th>
          <th>Vacancies</th>
          <th>Qualification</th>
          <th>Cadre</th>
          <th>Live Status</th>
          <th style="text-align: right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($jobs)): ?>
          <tr>
            <td colspan="7" style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
              <div style="font-size: 2rem; margin-bottom: 0.5rem;">📭</div>
              <strong>No recruitment notices match your query.</strong>
              <p style="font-size: 0.8rem; margin-top: 0.35rem;">Try adjusting the filter keyword or add a new job notification.</p>
            </td>
          </tr>
        <?php endif; ?>

        <?php foreach ($jobs as $job): ?>
          <tr id="job-row-<?= $job['id'] ?>">
            <td>
              <span class="admin-id-badge">#<?= $job['id'] ?></span>
            </td>
            <td>
              <div style="display: flex; align-items: center; gap: 0.45rem; margin-bottom: 0.2rem;">
                <span class="admin-badge badge-org" style="font-size: 0.65rem;">
                  <?= htmlspecialchars($job['organization_name']) ?>
                </span>
                <a href="/jobs/<?= htmlspecialchars($job['slug']) ?>" target="_blank" class="admin-job-link">
                  <?= htmlspecialchars($job['title']) ?>
                </a>
              </div>
              <div style="font-size: 0.75rem; color: var(--text-muted);">
                Advt: <?= htmlspecialchars($job['advertisement_number'] ?: 'Official Notice') ?> • Year <?= $job['year'] ?>
              </div>
            </td>
            <td style="font-weight: 800; color: var(--primary-ruby); white-space: nowrap;">
              <?= $job['total_vacancies'] ? number_format($job['total_vacancies']) . ' Posts' : 'As per Notice' ?>
            </td>
            <td style="font-size: 0.825rem; max-width: 200px;">
              <?= htmlspecialchars(substr($job['qualification_level'] ?: 'Graduate in Any Discipline', 0, 32)) ?><?= strlen($job['qualification_level'] ?? '') > 32 ? '...' : '' ?>
            </td>
            <td>
              <span style="font-weight: 600; font-size: 0.8rem;">
                <?= htmlspecialchars($job['state_code'] === 'ALL' ? 'All India' : $job['state_code']) ?>
              </span>
            </td>
            <td>
              <select class="admin-form-control status-updater-select" data-job-id="<?= $job['id'] ?>" style="padding: 0.3rem 0.5rem; font-size: 0.775rem; width: 140px; font-weight: 600;">
                <option value="Active" <?= $job['status'] === 'Active' ? 'selected' : '' ?>>🟢 Active</option>
                <option value="Upcoming" <?= $job['status'] === 'Upcoming' ? 'selected' : '' ?>>⏳ Upcoming</option>
                <option value="Exam_Phase" <?= $job['status'] === 'Exam_Phase' ? 'selected' : '' ?>>📝 Exam Phase</option>
                <option value="Result_Declared" <?= $job['status'] === 'Result_Declared' ? 'selected' : '' ?>>🏆 Result Out</option>
                <option value="Archived" <?= $job['status'] === 'Archived' ? 'selected' : '' ?>>📦 Archived</option>
              </select>
            </td>
            <td style="text-align: right;">
              <div class="admin-action-btn-group" style="justify-content: flex-end;">
                <a href="/jobs/<?= htmlspecialchars($job['slug']) ?>" target="_blank" class="admin-btn admin-btn-glass admin-btn-icon-only" title="View Public Post">
                  👁️
                </a>
                <button onclick="editJob(<?= $job['id'] ?>)" class="admin-btn admin-btn-glass admin-btn-icon-only" title="Edit Job Details">
                  ✏️
                </button>
                <button onclick="deleteJob(<?= $job['id'] ?>)" class="admin-btn admin-btn-danger admin-btn-icon-only" title="Delete Notification">
                  🗑️
                </button>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- 3. Add Job Notification Modal -->
<div id="addJobModal" class="admin-modal-overlay">
  <div class="admin-modal-card">
    <div class="admin-modal-header">
      <h3 class="admin-modal-title">➕ Add Official Job Notification</h3>
      <button class="admin-modal-close-btn" onclick="closeModal('addJobModal')">&times;</button>
    </div>

    <form id="addJobForm">
      <div class="admin-modal-body">
        
        <div class="admin-form-section-title">1. BASIC GAZETTE NOTICE DETAILS</div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <div class="admin-form-group" style="grid-column: 1 / -1;">
            <label class="admin-form-label">Recruitment / Exam Title *</label>
            <input type="text" name="title" required placeholder="e.g. Combined Graduate Level Examination 2026" class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Recruiting Commission / Body *</label>
            <input type="text" name="organization_name" required placeholder="e.g. Staff Selection Commission (SSC)" class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Advertisement / Notice Ref No.</label>
            <input type="text" name="advertisement_number" placeholder="e.g. CEN 05/2026" class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Total Vacancies (Count)</label>
            <input type="number" name="total_vacancies" placeholder="e.g. 7500" class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">State / Region</label>
            <select name="state_code" class="admin-form-control">
              <option value="ALL">All India (Central Government)</option>
              <option value="DL">Delhi NCR</option>
              <option value="UP">Uttar Pradesh</option>
              <option value="BR">Bihar</option>
              <option value="RJ">Rajasthan</option>
              <option value="MP">Madhya Pradesh</option>
              <option value="MH">Maharashtra</option>
              <option value="WB">West Bengal</option>
            </select>
          </div>
        </div>

        <div class="admin-form-section-title">2. ELIGIBILITY & KEY SPECIFICATIONS</div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <div class="admin-form-group">
            <label class="admin-form-label">Qualification Required</label>
            <input type="text" name="qualification_level" placeholder="e.g. Bachelor's Degree in Any Discipline" class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Pay Scale / Pay Level</label>
            <input type="text" name="pay_scale" placeholder="e.g. Level 4 to Level 8 (₹25,500 - ₹1,51,100)" class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Age Limit (Range)</label>
            <input type="text" name="age_limit" placeholder="e.g. 18 to 32 Years" class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Application Fee</label>
            <input type="text" name="fee_details" placeholder="e.g. Gen/OBC: ₹100 | SC/ST/Female: ₹0" class="admin-form-control">
          </div>
        </div>

        <div class="admin-form-section-title">3. IMPORTANT TIMELINE DATES</div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <div class="admin-form-group">
            <label class="admin-form-label">Online Application Start Date</label>
            <input type="date" name="start_date" class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Last Date to Apply Online</label>
            <input type="date" name="last_date" class="admin-form-control">
          </div>
        </div>

        <div class="admin-form-section-title">4. OFFICIAL LINKS & SUMMARY</div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <div class="admin-form-group">
            <label class="admin-form-label">Official Website URL</label>
            <input type="url" name="official_website_url" placeholder="https://ssc.gov.in" class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Official Apply Online URL</label>
            <input type="url" name="official_apply_url" placeholder="https://ssc.gov.in/apply" class="admin-form-control">
          </div>

          <div class="admin-form-group" style="grid-column: 1 / -1;">
            <label class="admin-form-label">Official PDF Notification Link</label>
            <input type="url" name="primary_notification_url" placeholder="https://ssc.gov.in/notice.pdf" class="admin-form-control">
          </div>

          <div class="admin-form-group" style="grid-column: 1 / -1;">
            <label class="admin-form-label">Executive Brief Summary</label>
            <textarea name="summary" rows="3" placeholder="Brief 2-3 sentence overview explaining the recruitment cadre, selection stages, and post breakdown..." class="admin-form-control"></textarea>
          </div>
        </div>

      </div>

      <div class="admin-modal-footer">
        <button type="button" onclick="closeModal('addJobModal')" class="admin-btn admin-btn-glass admin-btn-sm">Cancel</button>
        <button type="submit" id="addJobSubmitBtn" class="admin-btn admin-btn-primary admin-btn-sm">Save & Publish Notice</button>
      </div>
    </form>
  </div>
</div>

<!-- 4. Edit Job Notification Modal -->
<div id="editJobModal" class="admin-modal-overlay">
  <div class="admin-modal-card">
    <div class="admin-modal-header">
      <h3 class="admin-modal-title">✏️ Edit Job Notification</h3>
      <button class="admin-modal-close-btn" onclick="closeModal('editJobModal')">&times;</button>
    </div>

    <form id="editJobForm">
      <input type="hidden" name="id" id="editJobId">
      <div class="admin-modal-body">
        
        <div class="admin-form-section-title">1. BASIC GAZETTE NOTICE DETAILS</div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <div class="admin-form-group" style="grid-column: 1 / -1;">
            <label class="admin-form-label">Recruitment / Exam Title *</label>
            <input type="text" name="title" id="editJobTitle" required class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Recruiting Commission / Body *</label>
            <input type="text" name="organization_name" id="editJobOrg" required class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Advertisement / Notice Ref No.</label>
            <input type="text" name="advertisement_number" id="editJobAdvt" class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Total Vacancies (Count)</label>
            <input type="number" name="total_vacancies" id="editJobVacancies" class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">State / Region</label>
            <select name="state_code" id="editJobState" class="admin-form-control">
              <option value="ALL">All India (Central Government)</option>
              <option value="DL">Delhi NCR</option>
              <option value="UP">Uttar Pradesh</option>
              <option value="BR">Bihar</option>
              <option value="RJ">Rajasthan</option>
              <option value="MP">Madhya Pradesh</option>
              <option value="MH">Maharashtra</option>
              <option value="WB">West Bengal</option>
            </select>
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Live Status</label>
            <select name="status" id="editJobStatus" class="admin-form-control">
              <option value="Active">🟢 Active</option>
              <option value="Upcoming">⏳ Upcoming</option>
              <option value="Exam_Phase">📝 Exam Phase</option>
              <option value="Result_Declared">🏆 Result Out</option>
              <option value="Archived">📦 Archived</option>
            </select>
          </div>
        </div>

        <div class="admin-form-section-title">2. ELIGIBILITY & KEY SPECIFICATIONS</div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <div class="admin-form-group">
            <label class="admin-form-label">Qualification Required</label>
            <input type="text" name="qualification_level" id="editJobQual" class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Pay Scale / Pay Level</label>
            <input type="text" name="pay_scale" id="editJobPayScale" class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Age Limit (Range)</label>
            <input type="text" name="age_limit" id="editJobAgeLimit" class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Application Fee</label>
            <input type="text" name="fee_details" id="editJobFee" class="admin-form-control">
          </div>
        </div>

        <div class="admin-form-section-title">3. IMPORTANT TIMELINE DATES</div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <div class="admin-form-group">
            <label class="admin-form-label">Online Application Start Date</label>
            <input type="date" name="start_date" id="editJobStartDate" class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Last Date to Apply Online</label>
            <input type="date" name="last_date" id="editJobLastDate" class="admin-form-control">
          </div>
        </div>

        <div class="admin-form-section-title">4. OFFICIAL LINKS & SUMMARY</div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <div class="admin-form-group">
            <label class="admin-form-label">Official Apply Online URL</label>
            <input type="url" name="official_apply_url" id="editJobApplyUrl" class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Official PDF Notification Link</label>
            <input type="url" name="primary_notification_url" id="editJobPdfUrl" class="admin-form-control">
          </div>

          <div class="admin-form-group" style="grid-column: 1 / -1;">
            <label class="admin-form-label">Executive Brief Summary</label>
            <textarea name="summary" id="editJobSummary" rows="3" class="admin-form-control"></textarea>
          </div>
        </div>

      </div>

      <div class="admin-modal-footer">
        <button type="button" onclick="closeModal('editJobModal')" class="admin-btn admin-btn-glass admin-btn-sm">Cancel</button>
        <button type="submit" id="editJobSubmitBtn" class="admin-btn admin-btn-primary admin-btn-sm">Update Job Notice</button>
      </div>
    </form>
  </div>
</div>

<script>
// 1. Add Job AJAX Form Submission
document.getElementById('addJobForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const form = e.target;
  const submitBtn = document.getElementById('addJobSubmitBtn');
  submitBtn.disabled = true;
  submitBtn.innerText = 'Publishing Notice...';

  const formData = new FormData(form);
  const data = Object.fromEntries(formData.entries());

  try {
    const res = await fetch('/api/v1/admin/recruitments/create', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    const result = await res.json();
    if (result.success) {
      alert(result.message || 'Recruitment notice created successfully!');
      closeModal('addJobModal');
      window.location.reload();
    } else {
      alert(result.error || 'Failed to create job notice.');
    }
  } catch (err) {
    alert('Network error while saving recruitment notice.');
  } finally {
    submitBtn.disabled = false;
    submitBtn.innerText = 'Save & Publish Notice';
  }
});

// 2. Fetch Job Details for Editing
async function editJob(id) {
  try {
    const res = await fetch(`/api/v1/admin/recruitments/get?id=${id}`);
    const result = await res.json();
    if (!result.success || !result.data) {
      alert(result.error || 'Could not fetch recruitment details.');
      return;
    }

    const d = result.data;
    document.getElementById('editJobId').value = d.id;
    document.getElementById('editJobTitle').value = d.title || '';
    document.getElementById('editJobOrg').value = d.organization_name || '';
    document.getElementById('editJobAdvt').value = d.advertisement_number || '';
    document.getElementById('editJobVacancies').value = d.total_vacancies || '';
    document.getElementById('editJobState').value = d.state_code || 'ALL';
    document.getElementById('editJobStatus').value = d.status || 'Active';
    document.getElementById('editJobQual').value = d.qualification_level || '';
    document.getElementById('editJobPayScale').value = d.pay_scale || '';
    document.getElementById('editJobAgeLimit').value = d.age_limit || '';
    document.getElementById('editJobFee').value = d.fee_details || '';
    document.getElementById('editJobStartDate').value = d.start_date || '';
    document.getElementById('editJobLastDate').value = d.last_date || '';
    document.getElementById('editJobApplyUrl').value = d.official_apply_url || '';
    document.getElementById('editJobPdfUrl').value = d.primary_notification_url || '';
    document.getElementById('editJobSummary').value = d.summary || '';

    openModal('editJobModal');
  } catch (err) {
    alert('Failed to load job details from server.');
  }
}

// 3. Edit Job Form Submission
document.getElementById('editJobForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const submitBtn = document.getElementById('editJobSubmitBtn');
  submitBtn.disabled = true;
  submitBtn.innerText = 'Updating Notice...';

  const formData = new FormData(e.target);
  const data = Object.fromEntries(formData.entries());

  try {
    const res = await fetch('/api/v1/admin/recruitments/update', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    const result = await res.json();
    if (result.success) {
      alert(result.message || 'Recruitment notice updated successfully!');
      closeModal('editJobModal');
      window.location.reload();
    } else {
      alert(result.error || 'Failed to update job notice.');
    }
  } catch (err) {
    alert('Network error while updating job notice.');
  } finally {
    submitBtn.disabled = false;
    submitBtn.innerText = 'Update Job Notice';
  }
});

// 4. Delete Job Action
async function deleteJob(id) {
  if (!confirm(`Are you sure you want to delete Recruitment #${id}? This will remove it from the live portal.`)) return;
  try {
    const res = await fetch('/api/v1/admin/recruitments/delete', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id })
    });
    const result = await res.json();
    if (result.success) {
      const row = document.getElementById(`job-row-${id}`);
      if (row) {
        row.style.opacity = '0';
        setTimeout(() => row.remove(), 250);
      }
    } else {
      alert(result.error || 'Failed to delete recruitment notice.');
    }
  } catch (err) {
    alert('Network error while deleting job notice.');
  }
}

// 5. Status Dropdown Auto-Save
document.querySelectorAll('.status-updater-select').forEach(select => {
  select.addEventListener('change', async () => {
    const jobId = select.getAttribute('data-job-id');
    const status = select.value;
    select.disabled = true;
    try {
      const res = await fetch('/api/v1/admin/update-status', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: jobId, status })
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
        alert(data.error || 'Failed to update status.');
      }
    } catch (err) {
      alert('Network error while updating job status.');
    } finally {
      select.disabled = false;
    }
  });
});
</script>

<?php require_once __DIR__ . '/partials/admin_layout_bottom.php'; ?>
