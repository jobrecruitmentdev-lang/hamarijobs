<?php
require_once __DIR__ . '/../../../backend/app/Database.php';
use App\Database;

$db = Database::getConnection();

// Fetch all Result & Merit List timeline events
$results = $db->query("
    SELECT re.*, r.title as recruitment_title, r.slug as recruitment_slug, COALESCE(r.organization_name, re.organization_name, 'Official') as effective_org
    FROM recruitment_events re
    LEFT JOIN recruitments r ON re.recruitment_id = r.id
    WHERE re.event_type IN ('RESULT_DECLARED', 'CUTOFF_RELEASED', 'FINAL_MERIT_LIST', 'ANSWER_KEY_RELEASED')
    ORDER BY re.event_date DESC, re.id DESC
")->fetchAll();

// Fetch Cutoff Records
$cutoffs = $db->query("
    SELECT c.*, e.name as exam_name, e.short_name as exam_short, r.title as recruitment_title
    FROM cutoff_records c
    LEFT JOIN exams e ON c.exam_id = e.id
    LEFT JOIN recruitments r ON c.recruitment_id = r.id
    ORDER BY c.year DESC, c.cutoff_marks DESC
")->fetchAll();

// Fetch active recruitments & exams for modal dropdown pickers
$recruitmentsList = $db->query("SELECT id, title, organization_name, year FROM recruitments ORDER BY updated_at DESC LIMIT 100")->fetchAll();
$examsList = $db->query("SELECT id, name, short_name, conducting_body FROM exams ORDER BY name ASC")->fetchAll();

$pageTitle = "Manage Results & Cutoffs — Admin Control Center";
$adminPageTitle = "Results & Cutoffs";
$adminPageHeading = "Recruitment Results, Selection Merit Lists & Official Cutoffs Intelligence";
require_once __DIR__ . '/partials/admin_icons.php';
$adminHeaderActionHtml = '<button onclick="openModal(\'addResultModal\')" class="admin-btn admin-btn-primary admin-btn-sm" style="margin-right: 0.5rem;">' . admin_icon('plus', '', 14) . ' Create Result Notice</button><button onclick="openModal(\'addCutoffModal\')" class="admin-btn admin-btn-glass admin-btn-sm">' . admin_icon('plus', '', 14) . ' Add Cutoff Score</button>';

require_once __DIR__ . '/partials/admin_layout_top.php';
?>

<!-- Top Statistics KPI Cards -->
<div class="admin-kpi-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); margin-bottom: 1.75rem;">
  <div class="admin-kpi-card">
    <div class="admin-kpi-header">
      <span class="admin-kpi-label">Declared Results</span>
      <div class="admin-kpi-icon ruby"><?= admin_icon('award', '', 18) ?></div>
    </div>
    <div class="admin-kpi-value" style="color: var(--primary-ruby);"><?= number_format(count(array_filter($results, fn($r) => $r['event_type'] === 'RESULT_DECLARED'))) ?></div>
    <div class="admin-kpi-subtext">
      Official Scorecards & Selection Notices
    </div>
  </div>

  <div class="admin-kpi-card">
    <div class="admin-kpi-header">
      <span class="admin-kpi-label">Final Merit Lists</span>
      <div class="admin-kpi-icon emerald"><?= admin_icon('scroll', '', 18) ?></div>
    </div>
    <div class="admin-kpi-value" style="color: var(--color-emerald);"><?= number_format(count(array_filter($results, fn($r) => $r['event_type'] === 'FINAL_MERIT_LIST'))) ?></div>
    <div class="admin-kpi-subtext">
      <span style="color: var(--color-emerald); font-weight: 700;">● Verified</span> Allotted Candidate Lists
    </div>
  </div>

  <div class="admin-kpi-card">
    <div class="admin-kpi-header">
      <span class="admin-kpi-label">Official Cutoff Records</span>
      <div class="admin-kpi-icon blue"><?= admin_icon('bar-chart', '', 18) ?></div>
    </div>
    <div class="admin-kpi-value" style="color: var(--color-blue);"><?= number_format(count($cutoffs)) ?></div>
    <div class="admin-kpi-subtext">
      Category Benchmark Scores Cataloged
    </div>
  </div>
</div>

<!-- Section 1: Results & Merit Lists -->
<div class="admin-card" style="margin-bottom: 2rem;">
  <div class="admin-card-header">
    <div class="admin-card-title-wrap">
      <h3 class="admin-card-title"><?= admin_icon('award', '', 18) ?> Published Exam Results & Scorecard Notices</h3>
      <p class="admin-card-desc">Showing <strong><?= count($results) ?></strong> official merit lists and selection notices</p>
    </div>
    <button onclick="openModal('addResultModal')" class="admin-btn admin-btn-primary admin-btn-sm">
      <?= admin_icon('plus', '', 14) ?> Create Result Notice
    </button>
  </div>

  <div class="admin-table-responsive">
    <table class="admin-table">
      <thead>
        <tr>
          <th style="width: 60px;">ID</th>
          <th>Result Title / Notification</th>
          <th>Organization / Recruitment</th>
          <th>Event Type</th>
          <th style="min-width: 170px;">Live Status</th>
          <th>Declaration Date</th>
          <th>Scorecard Link</th>
          <th style="text-align: right;">Actions</th>
        </tr>
      </thead>
      <tbody id="resultsTableBody">
        <?php if (empty($results)): ?>
          <tr id="emptyResultRow">
            <td colspan="8" style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
              <div style="font-size: 2rem; margin-bottom: 0.5rem;"></div>
              <strong>No results declared yet.</strong>
              <p style="font-size: 0.8rem; margin-top: 0.35rem;">Click "Create Result Notice" above to announce an official examination result.</p>
            </td>
          </tr>
        <?php endif; ?>

        <?php foreach ($results as $res): ?>
          <tr id="result-row-<?= $res['id'] ?>">
            <td>
              <span class="admin-id-badge">#<?= $res['id'] ?></span>
            </td>
            <td>
              <div style="font-weight: 700; color: var(--text-dark); font-size: 0.95rem;">
                <?= htmlspecialchars($res['event_title']) ?>
              </div>
              <?php if (!empty($res['details'])): ?>
                <div style="font-size: 0.775rem; color: var(--text-muted); margin-top: 0.2rem; max-width: 350px;">
                  <?= htmlspecialchars(substr($res['details'], 0, 70)) ?><?= strlen($res['details']) > 70 ? '...' : '' ?>
                </div>
              <?php endif; ?>
            </td>
            <td>
              <span class="admin-badge badge-org" style="font-size: 0.72rem; font-weight: 800;">
                <?= htmlspecialchars($res['effective_org']) ?>
              </span>
              <?php if (!empty($res['recruitment_title'])): ?>
                <div style="font-size: 0.775rem; color: var(--text-secondary); margin-top: 0.25rem;">
                  <?= htmlspecialchars(substr($res['recruitment_title'], 0, 45)) ?><?= strlen($res['recruitment_title']) > 45 ? '...' : '' ?>
                </div>
              <?php endif; ?>
            </td>
            <td>
              <?php
                $badgeClass = 'badge-active';
                $label = '✓ Result Declared';
                if ($res['event_type'] === 'FINAL_MERIT_LIST') {
                    $badgeClass = 'badge-active';
                    $label = '📜 Final Merit List';
                } elseif ($res['event_type'] === 'CUTOFF_RELEASED') {
                    $badgeClass = 'badge-org';
                    $label = 'Cutoff Score';
                } elseif ($res['event_type'] === 'ANSWER_KEY_RELEASED') {
                    $badgeClass = 'badge-urgent';
                    $label = 'Answer Key';
                }
              ?>
              <span class="admin-badge <?= $badgeClass ?>" style="font-size: 0.72rem;">
                <?= $label ?>
              </span>
            </td>
            <td>
              <?php $curStatus = $res['status'] ?? 'RELEASED'; ?>
              <select class="admin-form-control result-status-select" data-event-id="<?= $res['id'] ?>" style="font-size: 0.775rem; padding: 0.35rem 0.5rem; font-weight: 700; border-radius: 6px;">
                <option value="RELEASED" <?= ($curStatus === 'RELEASED' || $curStatus === 'DECLARED') ? 'selected' : '' ?>>🎉 Result Declared</option>
                <option value="PROVISIONAL_KEY" <?= $curStatus === 'PROVISIONAL_KEY' ? 'selected' : '' ?>>🔑 Provisional Key</option>
                <option value="FINAL_LIST" <?= $curStatus === 'FINAL_LIST' ? 'selected' : '' ?>>📜 Final Merit List</option>
                <option value="EXPECTED" <?= $curStatus === 'EXPECTED' ? 'selected' : '' ?>>⏳ Expected Soon</option>
                <option value="POSTPONED" <?= $curStatus === 'POSTPONED' ? 'selected' : '' ?>>⚠️ Withheld / Delayed</option>
              </select>
            </td>
            <td>
              <div style="font-weight: 700; color: var(--primary-ruby); font-size: 0.9rem;">
                <?= !empty($res['event_date']) ? date('d M Y', strtotime($res['event_date'])) : 'Declared' ?>
              </div>
            </td>
            <td>
              <?php if (!empty($res['reference_url'])): ?>
                <a href="<?= htmlspecialchars($res['reference_url']) ?>" target="_blank" class="admin-btn admin-btn-glass admin-btn-sm" style="font-size: 0.75rem; padding: 0.35rem 0.65rem;">
                  📄 PDF / Scorecard ↗
                </a>
              <?php else: ?>
                <span style="color: var(--text-muted); font-size: 0.8rem;">—</span>
              <?php endif; ?>
            </td>
            <td style="text-align: right;">
              <div class="admin-action-btn-group" style="justify-content: flex-end;">
                <button onclick="editResult(<?= $res['id'] ?>)" class="admin-btn admin-btn-glass admin-btn-icon-only" title="Edit Result Notice">
                  <?= admin_icon('edit', '', 15) ?>
                </button>
                <button onclick="deleteResult(<?= $res['id'] ?>)" class="admin-btn admin-btn-danger admin-btn-icon-only" title="Delete Result">
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

<!-- Section 2: Cutoffs Matrix -->
<div class="admin-card">
  <div class="admin-card-header">
    <div class="admin-card-title-wrap">
      <h3 class="admin-card-title">Category-wise Cutoff Records</h3>
      <p class="admin-card-desc">Showing <strong><?= count($cutoffs) ?></strong> recorded historical & latest qualifying cutoff benchmark marks</p>
    </div>
    <button onclick="openModal('addCutoffModal')" class="admin-btn admin-btn-glass admin-btn-sm">
      + Add Cutoff Score
    </button>
  </div>

  <div class="admin-table-responsive">
    <table class="admin-table">
      <thead>
        <tr>
          <th style="width: 60px;">ID</th>
          <th>Exam Name</th>
          <th>Year</th>
          <th>Category</th>
          <th>Cutoff Score</th>
          <th>Total Marks</th>
          <th>Qualifying Candidates</th>
          <th>Official Notice</th>
          <th style="text-align: right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($cutoffs)): ?>
          <tr>
            <td colspan="9" style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
              <div style="font-size: 2rem; margin-bottom: 0.5rem;"></div>
              <strong>No cutoff benchmarks added yet.</strong>
            </td>
          </tr>
        <?php endif; ?>

        <?php foreach ($cutoffs as $c): ?>
          <tr id="cutoff-row-<?= $c['id'] ?>">
            <td><span class="admin-id-badge">#<?= $c['id'] ?></span></td>
            <td style="font-weight: 700; color: var(--text-dark); font-size: 0.9rem;">
              <?= htmlspecialchars($c['exam_name'] ?: 'Exam #' . $c['exam_id']) ?>
            </td>
            <td><strong><?= $c['year'] ?></strong></td>
            <td>
              <span class="admin-badge badge-org" style="font-weight: 800; font-size: 0.72rem;">
                <?= htmlspecialchars($c['category']) ?>
              </span>
            </td>
            <td style="font-weight: 800; color: var(--primary-ruby); font-size: 1rem;">
              <?= $c['cutoff_marks'] ?>
            </td>
            <td style="font-size: 0.85rem; color: var(--text-muted);">/ <?= $c['total_marks'] ?></td>
            <td style="font-size: 0.85rem;"><?= $c['qualifying_candidates'] ? number_format($c['qualifying_candidates']) : '—' ?></td>
            <td>
              <?php if (!empty($c['official_notice_url'])): ?>
                <a href="<?= htmlspecialchars($c['official_notice_url']) ?>" target="_blank" class="admin-btn admin-btn-glass admin-btn-sm" style="font-size: 0.75rem;">
                  📄 PDF ↗
                </a>
              <?php else: ?>
                —
              <?php endif; ?>
            </td>
            <td style="text-align: right;">
              <div class="admin-action-btn-group" style="justify-content: flex-end;">
                <button onclick="editCutoff(<?= $c['id'] ?>)" class="admin-btn admin-btn-glass admin-btn-icon-only" title="Edit Cutoff Score">
                  <?= admin_icon('edit', '', 15) ?>
                </button>
                <button onclick="deleteCutoff(<?= $c['id'] ?>)" class="admin-btn admin-btn-danger admin-btn-icon-only" title="Delete Cutoff">
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

<!-- Add Result Modal -->
<div id="addResultModal" class="admin-modal-overlay">
  <div class="admin-modal-card">
    <div class="admin-modal-header">
      <h3 class="admin-modal-title"><?= admin_icon('plus', '', 18) ?> Publish Official Result / Merit List</h3>
      <button class="admin-modal-close-btn" onclick="closeModal('addResultModal')">&times;</button>
    </div>

    <form id="addResultForm">
      <div class="admin-modal-body">
        
        <div class="admin-form-section-title">1. RESULT NOTICE DETAILS</div>
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
          <div class="admin-form-group">
            <label class="admin-form-label">Result / Merit Title *</label>
            <input type="text" name="event_title" required placeholder="e.g. UPSC CSE 2026 Final Result & Recommended Merit List" class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Result Event Type *</label>
            <select name="event_type" required class="admin-form-control">
              <option value="RESULT_DECLARED">RESULT_DECLARED</option>
              <option value="FINAL_MERIT_LIST">📜 FINAL_MERIT_LIST</option>
              <option value="CUTOFF_RELEASED">CUTOFF_RELEASED</option>
              <option value="ANSWER_KEY_RELEASED">🔑 ANSWER_KEY_RELEASED</option>
            </select>
          </div>
        </div>

        <div class="admin-form-section-title">2. RECRUITMENT & COMMISSION</div>
        <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 1rem;">
          <div class="admin-form-group">
            <label class="admin-form-label">Link to Existing Recruitment (Optional)</label>
            <select name="recruitment_id" id="add_result_rec_id" class="admin-form-control" onchange="autoFillResultOrg(this)">
              <option value="">-- Standalone / Direct Authority Entry --</option>
              <?php foreach ($recruitmentsList as $r): ?>
                <option value="<?= $r['id'] ?>" data-org="<?= htmlspecialchars($r['organization_name']) ?>">
                  #<?= $r['id'] ?> - <?= htmlspecialchars($r['organization_name']) ?>: <?= htmlspecialchars($r['title']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Commission / Conducting Body</label>
            <input type="text" name="organization_name" id="add_result_org_name" placeholder="e.g. Union Public Service Commission" class="admin-form-control">
          </div>
        </div>

        <div class="admin-form-section-title">3. DECLARATION DATE, STATUS & DIRECT SCORECARD LINK</div>
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
          <div class="admin-form-group">
            <label class="admin-form-label">Declaration Date *</label>
            <input type="date" name="event_date" value="<?= date('Y-m-d') ?>" required class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Live Status</label>
            <select name="status" class="admin-form-control">
              <option value="RELEASED">🎉 Result Declared</option>
              <option value="PROVISIONAL_KEY">🔑 Provisional Key Out</option>
              <option value="FINAL_LIST">📜 Final Merit List</option>
              <option value="EXPECTED">⏳ Expected Soon</option>
              <option value="POSTPONED">⚠️ Withheld / Delayed</option>
            </select>
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Official Scorecard / Result PDF Link</label>
            <input type="url" name="reference_url" placeholder="https://upsc.gov.in/results" class="admin-form-control">
          </div>

          <div class="admin-form-group" style="grid-column: 1 / -1;">
            <label class="admin-form-label">Result Summary & Cutoff Highlights</label>
            <textarea name="details" rows="2" placeholder="e.g. Total 1,056 candidates recommended for appointment across IAS, IPS, and IFS cadres." class="admin-form-control"></textarea>
          </div>
        </div>

      </div>

      <div class="admin-modal-footer">
        <button type="button" class="admin-btn admin-btn-glass" onclick="closeModal('addResultModal')">Cancel</button>
        <button type="submit" id="saveResultBtn" class="admin-btn admin-btn-primary"><?= admin_icon('check', '', 14) ?> Publish Result Notice</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Result Modal -->
<div id="editResultModal" class="admin-modal-overlay">
  <div class="admin-modal-card">
    <div class="admin-modal-header">
      <h3 class="admin-modal-title"><?= admin_icon('edit', '', 18) ?> Edit Result / Merit List Notice</h3>
      <button class="admin-modal-close-btn" onclick="closeModal('editResultModal')">&times;</button>
    </div>

    <form id="editResultForm">
      <input type="hidden" name="id" id="edit_result_id">

      <div class="admin-modal-body">
        
        <div class="admin-form-section-title">1. NOTICE DETAILS</div>
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
          <div class="admin-form-group">
            <label class="admin-form-label">Result Title *</label>
            <input type="text" name="event_title" id="edit_result_title" required class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Notice Type *</label>
            <select name="event_type" id="edit_result_type" required class="admin-form-control">
              <option value="RESULT_DECLARED">Result Declared</option>
              <option value="FINAL_MERIT_LIST">Final Merit List</option>
              <option value="CUTOFF_RELEASED">CUTOFF_RELEASED</option>
              <option value="ANSWER_KEY_RELEASED">🔑 ANSWER_KEY_RELEASED</option>
            </select>
          </div>
        </div>

        <div class="admin-form-section-title">2. RECRUITMENT & ORGANIZATION</div>
        <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 1rem;">
          <div class="admin-form-group">
            <label class="admin-form-label">Linked Recruitment</label>
            <select name="recruitment_id" id="edit_result_rec_id" class="admin-form-control">
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
            <input type="text" name="organization_name" id="edit_result_org_name" class="admin-form-control">
          </div>
        </div>

        <div class="admin-form-section-title">3. DATES, STATUS & LINKS</div>
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
          <div class="admin-form-group">
            <label class="admin-form-label">Declaration Date</label>
            <input type="date" name="event_date" id="edit_result_date" required class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Live Status</label>
            <select name="status" id="edit_result_status" class="admin-form-control">
              <option value="RELEASED">🎉 Result Declared</option>
              <option value="PROVISIONAL_KEY">🔑 Provisional Key Out</option>
              <option value="FINAL_LIST">📜 Final Merit List</option>
              <option value="EXPECTED">⏳ Expected Soon</option>
              <option value="POSTPONED">⚠️ Withheld / Delayed</option>
            </select>
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Official Scorecard Link</label>
            <input type="url" name="reference_url" id="edit_result_reference_url" class="admin-form-control">
          </div>

          <div class="admin-form-group" style="grid-column: 1 / -1;">
            <label class="admin-form-label">Details / Notes</label>
            <textarea name="details" id="edit_result_details" rows="2" class="admin-form-control"></textarea>
          </div>
        </div>

      </div>

      <div class="admin-modal-footer">
        <button type="button" class="admin-btn admin-btn-glass" onclick="closeModal('editResultModal')">Cancel</button>
        <button type="submit" id="updateResultBtn" class="admin-btn admin-btn-primary">💾 Update Result Notice</button>
      </div>
    </form>
  </div>
</div>

<!-- Add Cutoff Modal -->
<div id="addCutoffModal" class="admin-modal-overlay">
  <div class="admin-modal-card">
    <div class="admin-modal-header">
      <h3 class="admin-modal-title"><?= admin_icon('plus', '', 18) ?> Add Official Cutoff Benchmark Score</h3>
      <button class="admin-modal-close-btn" onclick="closeModal('addCutoffModal')">&times;</button>
    </div>

    <form id="addCutoffForm">
      <div class="admin-modal-body">
        
        <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 1rem;">
          <div class="admin-form-group">
            <label class="admin-form-label">Target Examination *</label>
            <select name="exam_id" required class="admin-form-control">
              <option value="">-- Select Exam Hub --</option>
              <?php foreach ($examsList as $ex): ?>
                <option value="<?= $ex['id'] ?>">
                  <?= htmlspecialchars($ex['short_name']) ?> — <?= htmlspecialchars($ex['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Examination Year *</label>
            <input type="number" name="year" value="<?= date('Y') ?>" required class="admin-form-control">
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
          <div class="admin-form-group">
            <label class="admin-form-label">Category *</label>
            <select name="category" required class="admin-form-control">
              <option value="UR">UR (Unreserved / General)</option>
              <option value="OBC">OBC</option>
              <option value="EWS">EWS</option>
              <option value="SC">SC</option>
              <option value="ST">ST</option>
              <option value="PwD">PwD</option>
              <option value="Ex-Servicemen">Ex-Servicemen</option>
            </select>
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Cutoff Marks *</label>
            <input type="number" step="0.01" name="cutoff_marks" required placeholder="e.g. 142.50" class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Total Out of Marks *</label>
            <input type="number" step="0.01" name="total_marks" value="200" required class="admin-form-control">
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <div class="admin-form-group">
            <label class="admin-form-label">Qualifying Candidates Count</label>
            <input type="number" name="qualifying_candidates" placeholder="e.g. 14500" class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Official Notice PDF Link</label>
            <input type="url" name="official_notice_url" placeholder="https://ssc.gov.in/cutoff.pdf" class="admin-form-control">
          </div>
        </div>

      </div>

      <div class="admin-modal-footer">
        <button type="button" class="admin-btn admin-btn-glass" onclick="closeModal('addCutoffModal')">Cancel</button>
        <button type="submit" id="saveCutoffBtn" class="admin-btn admin-btn-primary">Save Cutoff Score</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Cutoff Modal -->
<div id="editCutoffModal" class="admin-modal-overlay">
  <div class="admin-modal-card">
    <div class="admin-modal-header">
      <h3 class="admin-modal-title"><?= admin_icon('edit', '', 18) ?> Edit Cutoff Benchmark Score</h3>
      <button class="admin-modal-close-btn" onclick="closeModal('editCutoffModal')">&times;</button>
    </div>

    <form id="editCutoffForm">
      <input type="hidden" name="id" id="edit_cutoff_id">
      <div class="admin-modal-body">
        
        <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 1rem;">
          <div class="admin-form-group">
            <label class="admin-form-label">Target Examination *</label>
            <select name="exam_id" id="edit_cutoff_exam_id" required class="admin-form-control">
              <option value="">-- Select Exam Hub --</option>
              <?php foreach ($examsList as $ex): ?>
                <option value="<?= $ex['id'] ?>">
                  <?= htmlspecialchars($ex['short_name']) ?> — <?= htmlspecialchars($ex['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Examination Year *</label>
            <input type="number" name="year" id="edit_cutoff_year" required class="admin-form-control">
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
          <div class="admin-form-group">
            <label class="admin-form-label">Category *</label>
            <select name="category" id="edit_cutoff_category" required class="admin-form-control">
              <option value="UR">UR (Unreserved / General)</option>
              <option value="OBC">OBC</option>
              <option value="EWS">EWS</option>
              <option value="SC">SC</option>
              <option value="ST">ST</option>
              <option value="PwD">PwD</option>
              <option value="Ex-Servicemen">Ex-Servicemen</option>
            </select>
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Cutoff Marks *</label>
            <input type="number" step="0.01" name="cutoff_marks" id="edit_cutoff_marks" required class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Total Out of Marks *</label>
            <input type="number" step="0.01" name="total_marks" id="edit_cutoff_total_marks" required class="admin-form-control">
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <div class="admin-form-group">
            <label class="admin-form-label">Qualifying Candidates Count</label>
            <input type="number" name="qualifying_candidates" id="edit_cutoff_qualifying_candidates" class="admin-form-control">
          </div>

          <div class="admin-form-group">
            <label class="admin-form-label">Official Notice PDF Link</label>
            <input type="url" name="official_notice_url" id="edit_cutoff_official_notice_url" class="admin-form-control">
          </div>
        </div>

      </div>

      <div class="admin-modal-footer">
        <button type="button" class="admin-btn admin-btn-glass" onclick="closeModal('editCutoffModal')">Cancel</button>
        <button type="submit" id="updateCutoffBtn" class="admin-btn admin-btn-primary">Update Cutoff Score</button>
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

function autoFillResultOrg(sel) {
  const opt = sel.options[sel.selectedIndex];
  const org = opt.getAttribute('data-org');
  if (org) {
    document.getElementById('add_result_org_name').value = org;
  }
}

// Add Result Handler
document.getElementById('addResultForm')?.addEventListener('submit', async function(e) {
  e.preventDefault();
  const btn = document.getElementById('saveResultBtn');
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
      alert('❌ Error: ' + (data.error || 'Failed to publish result'));
    }
  } catch (err) {
    alert('❌ Connection failed: ' + err.message);
  } finally {
    btn.disabled = false;
    btn.innerText = '💾 Publish Result Notice';
  }
});

// Edit Result Fetch & Populate
async function editResult(id) {
  try {
    const res = await fetch(`/api/v1/admin/events/get?id=${id}`);
    const data = await res.json();

    if (!data.success || !data.data) {
      alert('❌ Could not load result data.');
      return;
    }

    const ev = data.data;
    document.getElementById('edit_result_id').value = ev.id;
    document.getElementById('edit_result_title').value = ev.event_title || '';
    document.getElementById('edit_result_type').value = ev.event_type || 'RESULT_DECLARED';
    document.getElementById('edit_result_status').value = ev.status || 'RELEASED';
    document.getElementById('edit_result_rec_id').value = ev.recruitment_id || '';
    document.getElementById('edit_result_org_name').value = ev.organization_name || ev.rec_org_name || '';
    document.getElementById('edit_result_date').value = ev.event_date || '';
    document.getElementById('edit_result_reference_url').value = ev.reference_url || '';
    document.getElementById('edit_result_details').value = ev.details || '';

    openModal('editResultModal');
  } catch (err) {
    alert('❌ Error fetching details: ' + err.message);
  }
}

// Update Result Handler
document.getElementById('editResultForm')?.addEventListener('submit', async function(e) {
  e.preventDefault();
  const btn = document.getElementById('updateResultBtn');
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
      alert('❌ Error: ' + (data.error || 'Failed to update result'));
    }
  } catch (err) {
    alert('❌ Connection failed: ' + err.message);
  } finally {
    btn.disabled = false;
    btn.innerText = '💾 Update Result Notice';
  }
});

// Delete Result
async function deleteResult(id) {
  if (!confirm(`Are you sure you want to delete Result Event #${id}? This action cannot be undone.`)) {
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
      const row = document.getElementById(`result-row-${id}`);
      if (row) {
        row.style.opacity = '0';
        setTimeout(() => row.remove(), 250);
      }
      alert(data.message || 'Result deleted successfully!');
    } else {
      alert('Error: ' + (data.error || 'Failed to delete result'));
    }
  } catch (err) {
    alert('Connection failed: ' + err.message);
  }
}

// Edit Cutoff Fetch & Populate
async function editCutoff(id) {
  try {
    const res = await fetch(`/api/v1/admin/cutoffs/get?id=${id}`);
    const data = await res.json();

    if (!data.success || !data.data) {
      alert('Could not load cutoff details.');
      return;
    }

    const c = data.data;
    document.getElementById('edit_cutoff_id').value = c.id;
    document.getElementById('edit_cutoff_exam_id').value = c.exam_id;
    document.getElementById('edit_cutoff_year').value = c.year;
    document.getElementById('edit_cutoff_category').value = c.category;
    document.getElementById('edit_cutoff_marks').value = c.cutoff_marks;
    document.getElementById('edit_cutoff_total_marks').value = c.total_marks;
    document.getElementById('edit_cutoff_qualifying_candidates').value = c.qualifying_candidates || '';
    document.getElementById('edit_cutoff_official_notice_url').value = c.official_notice_url || '';

    openModal('editCutoffModal');
  } catch (err) {
    alert('Error fetching details: ' + err.message);
  }
}

// Add Cutoff Handler
document.getElementById('addCutoffForm')?.addEventListener('submit', async function(e) {
  e.preventDefault();
  const btn = document.getElementById('saveCutoffBtn');
  btn.disabled = true;
  btn.innerText = 'Saving...';

  const formData = new FormData(this);
  const payload = Object.fromEntries(formData.entries());

  try {
    const res = await fetch('/api/v1/admin/cutoffs/create', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json();

    if (data.success) {
      alert(data.message || 'Cutoff score added successfully!');
      window.location.reload();
    } else {
      alert('Error: ' + (data.error || 'Failed to add cutoff'));
    }
  } catch (err) {
    alert('Connection failed: ' + err.message);
  } finally {
    btn.disabled = false;
    btn.innerText = 'Save Cutoff Score';
  }
});

// Update Cutoff Handler
document.getElementById('editCutoffForm')?.addEventListener('submit', async function(e) {
  e.preventDefault();
  const btn = document.getElementById('updateCutoffBtn');
  btn.disabled = true;
  btn.innerText = 'Updating...';

  const formData = new FormData(this);
  const payload = Object.fromEntries(formData.entries());

  try {
    const res = await fetch('/api/v1/admin/cutoffs/update', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json();

    if (data.success) {
      alert(data.message || 'Cutoff score updated successfully!');
      window.location.reload();
    } else {
      alert('Error: ' + (data.error || 'Failed to update cutoff'));
    }
  } catch (err) {
    alert('Connection failed: ' + err.message);
  } finally {
    btn.disabled = false;
    btn.innerText = 'Update Cutoff Score';
  }
});

// Delete Cutoff
async function deleteCutoff(id) {
  if (!confirm(`Are you sure you want to delete Cutoff Record #${id}?`)) {
    return;
  }

  try {
    const res = await fetch('/api/v1/admin/cutoffs/delete', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: id })
    });
    const data = await res.json();

    if (data.success) {
      const row = document.getElementById(`cutoff-row-${id}`);
      if (row) {
        row.style.opacity = '0';
        setTimeout(() => row.remove(), 250);
      }
      alert(data.message || 'Cutoff record deleted successfully!');
    } else {
      alert('Error: ' + (data.error || 'Failed to delete cutoff'));
    }
  } catch (err) {
    alert('Connection failed: ' + err.message);
  }
}

// Live Inline Status Auto-Save for Results Table
document.querySelectorAll('.result-status-select').forEach(select => {
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
      alert('❌ Network error while updating result status.');
    } finally {
      select.disabled = false;
    }
  });
});
</script>

<?php require_once __DIR__ . '/partials/admin_layout_bottom.php'; ?>
