<?php
require_once __DIR__ . '/../../../backend/app/Database.php';
use App\Database;

$db = Database::getConnection();

$articles = $db->query("
    SELECT a.*, r.title as recruitment_title, r.organization_name 
    FROM articles a 
    LEFT JOIN recruitments r ON a.recruitment_id = r.id 
    ORDER BY a.published_at DESC 
    LIMIT 100
")->fetchAll();

$recs = $db->query("SELECT id, title, organization_name FROM recruitments WHERE status = 'Active' ORDER BY organization_name ASC")->fetchAll();

$pageTitle = "Manage Preparation Guides — Admin Control Center";
$adminPageTitle = "Preparation Guides";
$adminPageHeading = "Editorial Intelligence & Preparation Guides";
$adminHeaderActionHtml = '<button onclick="openModal(\'addArticleModal\')" class="admin-btn admin-btn-primary admin-btn-sm">➕ Create Guide Article</button>';

require_once __DIR__ . '/partials/admin_layout_top.php';
?>

<!-- Editorial Guides Card -->
<div class="admin-card">
  <div class="admin-card-header">
    <div class="admin-card-title-wrap">
      <h3 class="admin-card-title">📚 Published Editorial Guides</h3>
      <p class="admin-card-desc">Showing <strong><?= count($articles) ?></strong> notification guides, syllabus breakdowns, and cutoff analysis reports</p>
    </div>
    <button onclick="openModal('addArticleModal')" class="admin-btn admin-btn-primary admin-btn-sm">
      ➕ Create Guide Article
    </button>
  </div>

  <div class="admin-table-responsive">
    <table class="admin-table">
      <thead>
        <tr>
          <th style="width: 70px;">ID</th>
          <th>Article Title / Slug</th>
          <th>Guide Type</th>
          <th>Linked Recruitment</th>
          <th>Read Time</th>
          <th>Quality Score</th>
          <th style="text-align: right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($articles)): ?>
          <tr>
            <td colspan="7" style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
              <div style="font-size: 2rem; margin-bottom: 0.5rem;">📚</div>
              <strong>No preparation guides published yet.</strong>
              <p style="font-size: 0.8rem; margin-top: 0.35rem;">Click "Create Guide Article" above to publish your first editorial guide.</p>
            </td>
          </tr>
        <?php endif; ?>

        <?php foreach ($articles as $art): ?>
          <tr id="article-row-<?= $art['id'] ?>">
            <td>
              <span class="admin-id-badge">#<?= $art['id'] ?></span>
            </td>
            <td>
              <strong style="color: var(--text-dark); font-size: 0.925rem;"><?= htmlspecialchars($art['title']) ?></strong>
              <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.15rem;">
                Slug: /articles/<?= htmlspecialchars($art['slug']) ?>
              </div>
            </td>
            <td>
              <span class="admin-badge badge-org" style="font-size: 0.68rem;">
                <?= htmlspecialchars(str_replace('_', ' ', $art['article_type'])) ?>
              </span>
            </td>
            <td>
              <?php if (!empty($art['recruitment_title'])): ?>
                <span style="font-size: 0.825rem; font-weight: 700; color: var(--primary-ruby);">
                  [<?= htmlspecialchars($art['organization_name']) ?>] <?= htmlspecialchars(substr($art['recruitment_title'], 0, 24)) ?>...
                </span>
              <?php else: ?>
                <span style="font-size: 0.8rem; color: var(--text-muted);">General Guide</span>
              <?php endif; ?>
            </td>
            <td style="font-size: 0.825rem; color: var(--text-muted); white-space: nowrap;">
              ⏱ <?= $art['reading_time_minutes'] ?> mins
            </td>
            <td>
              <span class="admin-badge badge-active">
                ✓ <?= $art['quality_score'] ?>/100
              </span>
            </td>
            <td style="text-align: right;">
              <div class="admin-action-btn-group" style="justify-content: flex-end;">
                <a href="/articles/<?= htmlspecialchars($art['slug']) ?>" target="_blank" class="admin-btn admin-btn-glass admin-btn-icon-only" title="View Published Guide">
                  👁️
                </a>
                <button onclick="editArticle(<?= $art['id'] ?>)" class="admin-btn admin-btn-glass admin-btn-icon-only" title="Edit Guide Article">
                  ✏️
                </button>
                <button onclick="deleteArticle(<?= $art['id'] ?>)" class="admin-btn admin-btn-danger admin-btn-icon-only" title="Delete Guide Article">
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

<!-- Add Article Modal -->
<div id="addArticleModal" class="admin-modal-overlay">
  <div class="admin-modal-card">
    <div class="admin-modal-header">
      <h3 class="admin-modal-title">➕ Publish New Preparation Guide</h3>
      <button class="admin-modal-close-btn" onclick="closeModal('addArticleModal')">&times;</button>
    </div>

    <form id="addArticleForm">
      <div class="admin-modal-body">
        
        <div class="admin-form-group">
          <label class="admin-form-label">Article Title *</label>
          <input type="text" name="title" required placeholder="e.g. SSC CGL 2026: Complete Preparation Roadmap & Strategy" class="admin-form-control">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <div class="admin-form-group">
            <label class="admin-form-label">Guide Category / Type</label>
            <select name="article_type" class="admin-form-control">
              <option value="Notification_Guide">Notification Guide</option>
              <option value="Eligibility_Guide">Eligibility Guide</option>
              <option value="Syllabus_Breakdown">Syllabus Breakdown</option>
              <option value="Exam_Pattern">Exam Pattern</option>
              <option value="Preparation_Strategy">Preparation Strategy</option>
              <option value="Cutoff_Analysis">Cutoff Analysis</option>
              <option value="Previous_Year_Analysis">Previous Year Analysis</option>
            </select>
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Linked Official Recruitment</label>
            <select name="recruitment_id" class="admin-form-control">
              <option value="">-- No Linked Recruitment --</option>
              <?php foreach ($recs as $r): ?>
                <option value="<?= $r['id'] ?>">[<?= htmlspecialchars($r['organization_name']) ?>] <?= htmlspecialchars($r['title']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Estimated Reading Time (Mins)</label>
            <input type="number" name="reading_time_minutes" value="5" min="1" max="60" class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Verified Quality Score (out of 100)</label>
            <input type="number" name="quality_score" value="100" min="80" max="100" class="admin-form-control">
          </div>
        </div>

        <div class="admin-form-group">
          <label class="admin-form-label">Summary / Excerpt *</label>
          <textarea name="excerpt" rows="2" required placeholder="Brief 2-sentence summary for search engines and preview cards..." class="admin-form-control"></textarea>
        </div>

        <div class="admin-form-group">
          <label class="admin-form-label">Complete Markdown Content *</label>
          <textarea name="content" rows="8" required placeholder="# Main Heading&#10;&#10;## Section Heading&#10;&#10;Write detailed guide in markdown format..." class="admin-form-control" style="font-family: var(--font-mono); font-size: 0.85rem;"></textarea>
        </div>

      </div>

      <div class="admin-modal-footer">
        <button type="button" onclick="closeModal('addArticleModal')" class="admin-btn admin-btn-glass admin-btn-sm">Cancel</button>
        <button type="submit" id="addArticleSubmitBtn" class="admin-btn admin-btn-primary admin-btn-sm">Publish Guide Article</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Article Modal -->
<div id="editArticleModal" class="admin-modal-overlay">
  <div class="admin-modal-card">
    <div class="admin-modal-header">
      <h3 class="admin-modal-title">✏️ Edit Preparation Guide</h3>
      <button class="admin-modal-close-btn" onclick="closeModal('editArticleModal')">&times;</button>
    </div>

    <form id="editArticleForm">
      <input type="hidden" name="id" id="editArticleId">
      <div class="admin-modal-body">
        
        <div class="admin-form-group">
          <label class="admin-form-label">Article Title *</label>
          <input type="text" name="title" id="editArticleTitle" required class="admin-form-control">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <div class="admin-form-group">
            <label class="admin-form-label">Guide Category / Type</label>
            <select name="article_type" id="editArticleType" class="admin-form-control">
              <option value="Notification_Guide">Notification Guide</option>
              <option value="Eligibility_Guide">Eligibility Guide</option>
              <option value="Syllabus_Breakdown">Syllabus Breakdown</option>
              <option value="Exam_Pattern">Exam Pattern</option>
              <option value="Preparation_Strategy">Preparation Strategy</option>
              <option value="Cutoff_Analysis">Cutoff Analysis</option>
              <option value="Previous_Year_Analysis">Previous Year Analysis</option>
            </select>
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Linked Official Recruitment</label>
            <select name="recruitment_id" id="editArticleRecId" class="admin-form-control">
              <option value="">-- No Linked Recruitment --</option>
              <?php foreach ($recs as $r): ?>
                <option value="<?= $r['id'] ?>">[<?= htmlspecialchars($r['organization_name']) ?>] <?= htmlspecialchars($r['title']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Estimated Reading Time (Mins)</label>
            <input type="number" name="reading_time_minutes" id="editArticleReadingTime" min="1" max="60" class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Verified Quality Score (out of 100)</label>
            <input type="number" name="quality_score" id="editArticleQualityScore" min="80" max="100" class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Publication Status</label>
            <select name="status" id="editArticleStatus" class="admin-form-control">
              <option value="Published">Published</option>
              <option value="Draft">Draft</option>
              <option value="Archived">Archived</option>
            </select>
          </div>
        </div>

        <div class="admin-form-group">
          <label class="admin-form-label">Summary / Excerpt *</label>
          <textarea name="excerpt" id="editArticleExcerpt" rows="2" required class="admin-form-control"></textarea>
        </div>

        <div class="admin-form-group">
          <label class="admin-form-label">Complete Markdown Content *</label>
          <textarea name="content" id="editArticleContent" rows="8" required class="admin-form-control" style="font-family: var(--font-mono); font-size: 0.85rem;"></textarea>
        </div>

      </div>

      <div class="admin-modal-footer">
        <button type="button" onclick="closeModal('editArticleModal')" class="admin-btn admin-btn-glass admin-btn-sm">Cancel</button>
        <button type="submit" id="editArticleSubmitBtn" class="admin-btn admin-btn-primary admin-btn-sm">Update Guide Article</button>
      </div>
    </form>
  </div>
</div>

<script>
// 1. Add Article Form AJAX
document.getElementById('addArticleForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const submitBtn = document.getElementById('addArticleSubmitBtn');
  submitBtn.disabled = true;
  submitBtn.innerText = 'Publishing...';

  const formData = new FormData(e.target);
  const data = Object.fromEntries(formData.entries());

  try {
    const res = await fetch('/api/v1/admin/articles/create', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    const result = await res.json();
    if (result.success) {
      alert(result.message || 'Guide published successfully!');
      closeModal('addArticleModal');
      window.location.reload();
    } else {
      alert(result.error || 'Failed to publish article.');
    }
  } catch (err) {
    alert('Network error while saving guide.');
  } finally {
    submitBtn.disabled = false;
    submitBtn.innerText = 'Publish Guide Article';
  }
});

// 2. Fetch Article Details for Editing
async function editArticle(id) {
  try {
    const res = await fetch(`/api/v1/admin/articles/get?id=${id}`);
    const result = await res.json();
    if (!result.success || !result.data) {
      alert(result.error || 'Could not fetch guide details.');
      return;
    }

    const d = result.data;
    document.getElementById('editArticleId').value = d.id;
    document.getElementById('editArticleTitle').value = d.title || '';
    document.getElementById('editArticleType').value = d.article_type || 'Notification_Guide';
    document.getElementById('editArticleRecId').value = d.recruitment_id || '';
    document.getElementById('editArticleReadingTime').value = d.reading_time_minutes || 5;
    document.getElementById('editArticleQualityScore').value = d.quality_score || 95;
    document.getElementById('editArticleStatus').value = d.status || 'Published';
    document.getElementById('editArticleExcerpt').value = d.excerpt || '';
    document.getElementById('editArticleContent').value = d.content || '';

    openModal('editArticleModal');
  } catch (err) {
    alert('Failed to load guide details from server.');
  }
}

// 3. Edit Article Form AJAX Submission
document.getElementById('editArticleForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const submitBtn = document.getElementById('editArticleSubmitBtn');
  submitBtn.disabled = true;
  submitBtn.innerText = 'Updating...';

  const formData = new FormData(e.target);
  const data = Object.fromEntries(formData.entries());

  try {
    const res = await fetch('/api/v1/admin/articles/update', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    const result = await res.json();
    if (result.success) {
      alert(result.message || 'Guide updated successfully!');
      closeModal('editArticleModal');
      window.location.reload();
    } else {
      alert(result.error || 'Failed to update article.');
    }
  } catch (err) {
    alert('Network error while updating guide.');
  } finally {
    submitBtn.disabled = false;
    submitBtn.innerText = 'Update Guide Article';
  }
});

// 4. Delete Article Action
async function deleteArticle(id) {
  if (!confirm(`Are you sure you want to delete Guide Article #${id}? This will remove it from the live portal.`)) return;
  try {
    const res = await fetch('/api/v1/admin/articles/delete', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id })
    });
    const result = await res.json();
    if (result.success) {
      const row = document.getElementById(`article-row-${id}`);
      if (row) {
        row.style.opacity = '0';
        setTimeout(() => row.remove(), 250);
      }
    } else {
      alert(result.error || 'Failed to delete guide article.');
    }
  } catch (err) {
    alert('Network error while deleting guide.');
  }
}
</script>

<?php require_once __DIR__ . '/partials/admin_layout_bottom.php'; ?>
