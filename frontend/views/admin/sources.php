<?php
require_once __DIR__ . '/../../../backend/app/Database.php';
use App\Database;

$db = Database::getConnection();

$sources = $db->query("SELECT * FROM source_registry ORDER BY priority DESC, source_name ASC")->fetchAll();

$pageTitle = "Monitored Sources — Admin Control Center";
$adminPageTitle = "Monitored Sources";
$adminPageHeading = "Official Gazette Portals & Web Scraper Registry";
$adminHeaderActionHtml = '<button onclick="openModal(\'addSourceModal\')" class="admin-btn admin-btn-primary admin-btn-sm">+ Register Source</button>';

require_once __DIR__ . '/partials/admin_layout_top.php';
?>

<!-- Monitored Sources Directory Card -->
<div class="admin-card">
  <div class="admin-card-header">
    <div class="admin-card-title-wrap">
      <h3 class="admin-card-title">Active Gazette Sources</h3>
      <p class="admin-card-desc">Showing <strong><?= count($sources) ?></strong> monitored official recruitment portals and autonomous crawling endpoints</p>
    </div>
    <button onclick="openModal('addSourceModal')" class="admin-btn admin-btn-primary admin-btn-sm">
      + Register Source
    </button>
  </div>

  <div class="admin-table-responsive">
    <table class="admin-table">
      <thead>
        <tr>
          <th style="width: 70px;">ID</th>
          <th>Source Name</th>
          <th>Official Base URL</th>
          <th>Portal Type</th>
          <th>Frequency</th>
          <th>Priority</th>
          <th style="text-align: right;">Daemon Health</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($sources as $s): 
          $sourceUrl = $s['base_url'] ?? $s['website_url'] ?? $s['recruitment_url'] ?? 'https://gov.in';
          $frequency = $s['crawl_frequency'] ?? 'Daily';
          $priorityVal = $s['priority'] ?? 'High';
        ?>
          <tr>
            <td>
              <span class="admin-id-badge">#<?= $s['id'] ?></span>
            </td>
            <td>
              <strong style="color: var(--text-dark); font-size: 0.925rem;"><?= htmlspecialchars($s['source_name']) ?></strong>
            </td>
            <td>
              <a href="<?= htmlspecialchars($sourceUrl) ?>" target="_blank" class="admin-job-link" style="color: var(--primary-ruby); font-size: 0.85rem; font-weight: 600;">
                <?= htmlspecialchars($sourceUrl) ?> ↗
              </a>
            </td>
            <td>
              <span class="admin-badge badge-org" style="font-size: 0.68rem;">
                <?= htmlspecialchars($s['source_type'] ?? 'Central') ?>
              </span>
            </td>
            <td style="font-size: 0.85rem; color: var(--text-muted);">
              <?= htmlspecialchars($frequency) ?>
            </td>
            <td>
              <span class="admin-badge" style="background: var(--color-blue-bg); color: var(--color-blue); border: 1px solid var(--color-blue-border); font-weight: 800;">
                <?= is_numeric($priorityVal) ? 'P-' . $priorityVal : $priorityVal ?>
              </span>
            </td>
            <td style="text-align: right;">
              <span class="admin-badge badge-active">✓ Operational</span>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Source Modal -->
<div id="addSourceModal" class="admin-modal-overlay">
  <div class="admin-modal-card">
    <div class="admin-modal-header">
      <h3 class="admin-modal-title">+ Register New Monitored Portal</h3>
      <button class="admin-modal-close-btn" onclick="closeModal('addSourceModal')">&times;</button>
    </div>

    <form id="addSourceForm">
      <div class="admin-modal-body">
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <div class="admin-form-group" style="grid-column: 1 / -1;">
            <label class="admin-form-label">Recruiting Portal Name *</label>
            <input type="text" name="source_name" required placeholder="e.g. State Bank of India Careers" class="admin-form-control">
          </div>

          <div class="admin-form-group" style="grid-column: 1 / -1;">
            <label class="admin-form-label">Official Base URL *</label>
            <input type="url" name="base_url" required placeholder="https://sbi.co.in/careers" class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Source Type</label>
            <select name="source_type" class="admin-form-control">
              <option value="Central">Central Ministry</option>
              <option value="State">State PSC</option>
              <option value="Railway">Railway</option>
              <option value="Banking">Banking</option>
              <option value="Defence">Defence</option>
              <option value="Other">Other</option>
            </select>
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Crawl Frequency</label>
            <select name="crawl_frequency" class="admin-form-control">
              <option value="Daily">Daily</option>
              <option value="Hourly">Hourly</option>
              <option value="Weekly">Weekly</option>
            </select>
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Priority (1 to 10)</label>
            <input type="number" name="priority" value="8" min="1" max="10" class="admin-form-control">
          </div>
        </div>

      </div>

      <div class="admin-modal-footer">
        <button type="button" onclick="closeModal('addSourceModal')" class="admin-btn admin-btn-glass admin-btn-sm">Cancel</button>
        <button type="submit" id="addSourceSubmitBtn" class="admin-btn admin-btn-primary admin-btn-sm">Register Portal Source</button>
      </div>
    </form>
  </div>
</div>

<script>
// Add Source Form AJAX
document.getElementById('addSourceForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const submitBtn = document.getElementById('addSourceSubmitBtn');
  submitBtn.disabled = true;
  submitBtn.innerText = 'Registering...';

  const formData = new FormData(e.target);
  const data = Object.fromEntries(formData.entries());

  try {
    const res = await fetch('/api/v1/admin/sources/create', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    const result = await res.json();
    if (result.success) {
      alert(result.message || 'Source registered successfully!');
      closeModal('addSourceModal');
      window.location.reload();
    } else {
      alert(result.error || 'Failed to register source.');
    }
  } catch (err) {
    alert('Network error while saving portal source.');
  } finally {
    submitBtn.disabled = false;
    submitBtn.innerText = 'Register Portal Source';
  }
});
</script>

<?php require_once __DIR__ . '/partials/admin_layout_bottom.php'; ?>
