<?php
require_once __DIR__ . '/../../backend/app/Database.php';
use App\Database;

$db = Database::getConnection();

// Fetch live active commissions from database
$commissions = $db->query("SELECT * FROM commissions WHERE is_active = 1 ORDER BY id ASC")->fetchAll();

// Query active vacancies for each commission
foreach ($commissions as $idx => $cItem) {
    $filterKw = !empty($cItem['filter_keyword']) ? $cItem['filter_keyword'] : $cItem['short_name'];
    $stmt = $db->prepare("SELECT COUNT(*) as active_count, SUM(total_vacancies) as total_vac FROM recruitments WHERE (organization_name LIKE ? OR title LIKE ?) AND status = 'Active'");
    $stmt->execute(["%{$filterKw}%", "%{$filterKw}%"]);
    $row = $stmt->fetch();
    $commissions[$idx]['active_openings'] = $row['active_count'] ?? 0;
    $commissions[$idx]['vacancies_sum'] = $row['total_vac'] ?? 0;
    $commissions[$idx]['short'] = $cItem['short_name'];
}

$pageTitle = "Government Recruiting Commissions Directory 2026 — UPSC, SSC, Railways, Banks & State PSCs";
$pageDesc = "Complete directory of official government recruiting commissions across India. Monitor active notifications, examination schedules, and official portals.";
require_once __DIR__ . '/partials/header.php';
?>

<div class="container" style="padding: 3rem 0 5rem;">
  
  <div style="margin-bottom: 2.5rem;">
    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.4rem;">
      <span class="badge-org">OFFICIAL COMMISSIONS</span>
      <span style="font-size: 0.825rem; color: var(--text-muted); font-weight: 600;">Constitutional & Autonomous Recruiting Bodies</span>
    </div>
    <h1 style="font-family: var(--font-heading); font-size: 2.35rem; font-weight: 800; color: var(--text-primary); letter-spacing: -0.02em;">
      Government Recruiting Commissions Directory
    </h1>
    <p style="color: var(--text-secondary); font-size: 1rem; margin-top: 0.25rem;">
      Explore individual commission landing dossiers, active vacancy counts, official headquarters, and verified notice archives.
    </p>
  </div>

  <div class="job-grid">
    <?php foreach ($commissions as $comm): ?>
      <div class="content-box" style="display: flex; flex-direction: column; justify-content: space-between; margin-bottom: 0;">
        <div>
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
              <?php
                $cIcon = 'landmark';
                $cSlug = strtolower($comm['slug'] ?? '');
                $cEmblem = trim($comm['emblem'] ?? '');
                if (!empty($cEmblem) && preg_match('/^[a-z0-9-]+$/', $cEmblem)) {
                    $cIcon = $cEmblem;
                } elseif ($cSlug === 'ssc') {
                    $cIcon = 'building-2';
                } elseif ($cSlug === 'railways') {
                    $cIcon = 'train';
                } elseif ($cSlug === 'banking') {
                    $cIcon = 'bank';
                } elseif ($cSlug === 'defence') {
                    $cIcon = 'plane';
                } elseif ($cSlug === 'state-psc') {
                    $cIcon = 'building';
                }
              ?>
              <div class="commission-icon" style="width: 44px; height: 44px; margin: 0; display: flex; align-items: center; justify-content: center;">
                <?= app_icon($cIcon, '', 22) ?>
              </div>
              <div>
                <span class="badge-org" style="font-size: 0.7rem;"><?= htmlspecialchars($comm['short']) ?></span>
                <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($comm['category']) ?></div>
              </div>
            </div>
            <span class="badge-active">
              <?= $comm['active_openings'] ?> Active Notices
            </span>
          </div>

          <h3 style="font-family: var(--font-heading); font-size: 1.25rem; font-weight: 800; color: var(--text-primary); margin-bottom: 0.5rem;">
            <a href="/commissions/<?= $comm['slug'] ?>">
              <?= htmlspecialchars($comm['name']) ?>
            </a>
          </h3>

          <p style="font-size: 0.9rem; color: var(--text-secondary); line-height: 1.6; margin-bottom: 1.25rem;">
            <?= htmlspecialchars($comm['description']) ?>
          </p>

          <div style="background: var(--bg-subtle); border-radius: var(--radius-sm); padding: 0.85rem 1rem; margin-bottom: 1.25rem; font-size: 0.825rem;">
            <div style="color: var(--text-muted); font-size: 0.725rem; font-weight: 700; text-transform: uppercase;">Headquarters</div>
            <div style="font-weight: 600; color: var(--text-primary); margin-top: 0.15rem;"><?= htmlspecialchars($comm['hq']) ?></div>
          </div>
        </div>

        <div style="display: flex; gap: 0.75rem; border-top: 1px solid var(--border-subtle); padding-top: 1rem;">
          <a href="/commissions/<?= $comm['slug'] ?>" class="btn btn-primary btn-sm" style="flex: 1;">
            View Commission Dossier &rarr;
          </a>
          <a href="<?= htmlspecialchars($comm['website']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-sm" title="Official Website">
            Portal
          </a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
