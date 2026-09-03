import os

admin_leads_file = r"C:\hk\prmarketing\backend\admin\leads.php"

content = """<?php
/**
 * PR Marketing Ventures — Client Leads & Tools Inquiries Intelligence Panel
 * Warm Cream & Light Brown Luxury Palette
 */
$pageTitle = "Client Data & Leads";

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../repositories/LeadRepository.php';

$leadRepo = new LeadRepository();
$msg = '';
$error = '';

// 1. Handle CSV Export
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    require_once __DIR__ . '/layout/header.php'; // Ensures authentication
    
    $leads = $leadRepo->getAll(10000, 0);
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="PR_Marketing_Client_Leads_' . date('Y-m-d_His') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    // UTF-8 BOM for Microsoft Excel compatibility
    fputs($output, "\\xEF\\xBB\\xBF");
    
    fputcsv($output, [
        'Lead ID',
        'Full Name',
        'Phone Number',
        'WhatsApp Number',
        'Business / Brand Name',
        'Website / Domain URL',
        'Business Category / Stage',
        'Tool Used',
        'Status',
        'Notes',
        'IP Address',
        'Date & Time'
    ]);
    
    foreach ($leads as $l) {
        fputcsv($output, [
            $l['id'],
            $l['full_name'],
            $l['phone_number'],
            $l['whatsapp_number'] ?: $l['phone_number'],
            $l['business_name'] ?: 'N/A',
            $l['website_url'] ?: 'N/A',
            $l['business_stage'] ?: 'N/A',
            $l['tool_used'] ?: 'N/A',
            $l['status'],
            $l['notes'] ?: '',
            $l['ip_address'] ?: '',
            $l['created_at']
        ]);
    }
    fclose($output);
    exit;
}

// 2. Handle Status Update via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $leadId = $_POST['lead_id'] ?? '';
    $newStatus = $_POST['status'] ?? 'New Lead';
    $notes = $_POST['notes'] ?? null;
    
    if ($leadId) {
        try {
            $leadRepo->updateStatus($leadId, $newStatus, $notes);
            $msg = "Lead status successfully updated to '{$newStatus}'!";
        } catch (Exception $e) {
            $error = "Error updating lead: " . $e->getMessage();
        }
    }
}

// 3. Handle Delete Lead
if (isset($_GET['action']) && $_GET['action'] === 'delete' && !empty($_GET['id'])) {
    try {
        $leadRepo->delete($_GET['id']);
        $msg = "Lead record deleted successfully!";
    } catch (Exception $e) {
        $error = "Error deleting lead: " . $e->getMessage();
    }
}

require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/layout/sidebar.php';

$search = $_GET['q'] ?? null;
$statusFilter = $_GET['status'] ?? 'All';
$toolFilter = $_GET['tool'] ?? 'All';

$leads = $leadRepo->getAll(200, 0, $search, $statusFilter, $toolFilter);
$stats = $leadRepo->getStats();
?>

<?php if ($msg): ?>
    <div style="padding: 0.875rem 1rem; border-radius: 0.75rem; background: var(--emerald-bg); border: 1px solid var(--emerald-border); color: var(--emerald-text); font-size: 0.75rem; font-weight: 800; display: flex; align-items: center; gap: 0.5rem;">
        <span>✓</span> <?= htmlspecialchars($msg) ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div style="padding: 0.875rem 1rem; border-radius: 0.75rem; background: var(--red-bg); border: 1px solid var(--red-border); color: var(--red-text); font-size: 0.75rem; font-weight: 800; display: flex; align-items: center; gap: 0.5rem;">
        <span>⚠</span> <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<!-- 1. KPI Statistics Overview Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
    <!-- Metric 1: Total Leads -->
    <div class="glass-card" style="display: flex; align-items: center; justify-content: space-between; padding: 1.25rem;">
        <div>
            <p style="font-size: 0.6875rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-dim);">Total Inquiries</p>
            <h3 style="font-size: 1.5rem; font-weight: 900; color: var(--text-main); margin-top: 0.25rem; font-family: 'Space Grotesk', sans-serif;"><?= number_format($stats['total']) ?></h3>
            <p style="font-size: 0.6875rem; color: var(--emerald-text); font-weight: 700; margin-top: 0.25rem;">● All Tools Captured</p>
        </div>
        <div style="width: 2.75rem; height: 2.75rem; border-radius: 0.75rem; background: #eee5d8; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; border: 1px solid var(--border-subtle);">
            👥
        </div>
    </div>

    <!-- Metric 2: New Leads -->
    <div class="glass-card" style="display: flex; align-items: center; justify-content: space-between; padding: 1.25rem;">
        <div>
            <p style="font-size: 0.6875rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-dim);">New Unread Leads</p>
            <h3 style="font-size: 1.5rem; font-weight: 900; color: var(--brown-primary); margin-top: 0.25rem; font-family: 'Space Grotesk', sans-serif;"><?= number_format($stats['new_leads']) ?></h3>
            <p style="font-size: 0.6875rem; color: var(--brown-primary); font-weight: 700; margin-top: 0.25rem;">Pending Discussion</p>
        </div>
        <div style="width: 2.75rem; height: 2.75rem; border-radius: 0.75rem; background: #fbf5ee; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; border: 1px solid var(--border-medium);">
            ⚡
        </div>
    </div>

    <!-- Metric 3: Today's Leads -->
    <div class="glass-card" style="display: flex; align-items: center; justify-content: space-between; padding: 1.25rem;">
        <div>
            <p style="font-size: 0.6875rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-dim);">Today's Inquiries</p>
            <h3 style="font-size: 1.5rem; font-weight: 900; color: var(--text-main); margin-top: 0.25rem; font-family: 'Space Grotesk', sans-serif;"><?= number_format($stats['today']) ?></h3>
            <p style="font-size: 0.6875rem; color: var(--text-muted); font-weight: 700; margin-top: 0.25rem;">Captured Today</p>
        </div>
        <div style="width: 2.75rem; height: 2.75rem; border-radius: 0.75rem; background: #eee5d8; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; border: 1px solid var(--border-subtle);">
            📅
        </div>
    </div>
</div>

<!-- 2. Main Client Data Table Section -->
<div class="glass-card" style="display: flex; flex-direction: column; gap: 1.25rem;">
    <!-- Top Action Bar -->
    <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem;">
        <div>
            <h3 style="font-size: 1.125rem; font-weight: 900; color: var(--text-main); font-family: 'Space Grotesk', sans-serif;">
                Client Tools Inquiries & Lead Data
            </h3>
            <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">
                Real-time inquiries captured across Domain Authority Checker, Google Review QR, and WhatsApp Link Tools.
            </p>
        </div>

        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <a href="/admin/leads.php?action=export_csv" class="brown-btn" style="padding: 0.55rem 1rem; font-size: 0.75rem;">
                📥 Export to Excel / CSV
            </a>
        </div>
    </div>

    <!-- Filter & Search Controls Bar -->
    <form method="GET" style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem; padding: 0.875rem; background: var(--bg-subtle); border-radius: 0.75rem; border: 1px solid var(--border-subtle);">
        <div style="flex: 1; min-width: 200px;">
            <input type="text" name="q" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Search by Client Name, Phone, Brand, Domain..." class="form-input" style="font-size: 0.75rem; padding: 0.5rem 0.875rem;">
        </div>

        <div style="min-width: 150px;">
            <select name="status" class="form-select" style="font-size: 0.75rem; padding: 0.5rem 0.875rem;" onchange="this.form.submit()">
                <option value="All" <?= ($statusFilter === 'All') ? 'selected' : '' ?>>All Statuses</option>
                <option value="New Lead" <?= ($statusFilter === 'New Lead') ? 'selected' : '' ?>>New Lead</option>
                <option value="In Discussion" <?= ($statusFilter === 'In Discussion') ? 'selected' : '' ?>>In Discussion</option>
                <option value="Converted" <?= ($statusFilter === 'Converted') ? 'selected' : '' ?>>Converted</option>
                <option value="Follow-up" <?= ($statusFilter === 'Follow-up') ? 'selected' : '' ?>>Follow-up Required</option>
            </select>
        </div>

        <div style="min-width: 170px;">
            <select name="tool" class="form-select" style="font-size: 0.75rem; padding: 0.5rem 0.875rem;" onchange="this.form.submit()">
                <option value="All" <?= ($toolFilter === 'All') ? 'selected' : '' ?>>All Tools</option>
                <?php foreach ($stats['tools'] as $t): ?>
                    <option value="<?= htmlspecialchars($t) ?>" <?= ($toolFilter === $t) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="cream-btn" style="padding: 0.5rem 0.875rem; font-size: 0.75rem;">
            Filter 🔍
        </button>

        <?php if ($search || $statusFilter !== 'All' || $toolFilter !== 'All'): ?>
            <a href="/admin/leads.php" class="cream-btn" style="padding: 0.5rem 0.875rem; font-size: 0.75rem; color: var(--red-text);">
                Clear ✕
            </a>
        <?php endif; ?>
    </form>

    <!-- Leads Interactive Data Table -->
    <?php if (empty($leads)): ?>
        <div style="padding: 3.5rem; text-align: center; font-size: 0.8125rem; color: var(--text-muted); background: var(--bg-subtle); border-radius: 0.875rem; border: 1px solid var(--border-subtle);">
            <p style="font-weight: 800; font-size: 0.9375rem; color: var(--text-main); margin-bottom: 0.35rem;">No client inquiries found</p>
            <p>Leads captured through website tools will automatically appear here in real-time.</p>
        </div>
    <?php else: ?>
        <div style="overflow-x: auto; border: 1px solid var(--border-subtle); border-radius: 0.75rem;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Client & Brand</th>
                        <th>Contact / WhatsApp</th>
                        <th>Website / Domain</th>
                        <th>Category / Stage</th>
                        <th>Source Tool</th>
                        <th>Status</th>
                        <th>Captured At</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leads as $lead): 
                        $rawPhone = preg_replace('/[^0-9]/', '', $lead['phone_number']);
                        $waPhone = (strlen($rawPhone) === 10) ? ('91' . $rawPhone) : $rawPhone;
                        $cleanUrl = $lead['website_url'] ? (str_starts_with($lead['website_url'], 'http') ? $lead['website_url'] : ('https://' . $lead['website_url'])) : '';
                    ?>
                        <tr>
                            <!-- 1. Full Name & Brand -->
                            <td style="max-width: 220px;">
                                <div style="font-weight: 800; color: var(--text-main); font-family: 'Space Grotesk', sans-serif; font-size: 0.875rem;">
                                    <?= htmlspecialchars($lead['full_name']) ?>
                                </div>
                                <div style="font-size: 0.75rem; color: var(--brown-primary); font-weight: 700; margin-top: 0.15rem;">
                                    🏢 <?= htmlspecialchars($lead['business_name'] ?: 'Brand N/A') ?>
                                </div>
                            </td>

                            <!-- 2. Phone & 1-Click WhatsApp -->
                            <td style="white-space: nowrap;">
                                <div style="font-weight: 700; font-size: 0.8125rem; color: var(--text-main); font-family: monospace;">
                                    <?= htmlspecialchars($lead['phone_number']) ?>
                                </div>
                                <div style="margin-top: 0.35rem; display: flex; align-items: center; gap: 0.375rem;">
                                    <a href="https://wa.me/<?= htmlspecialchars($waPhone) ?>?text=<?= urlencode("Hello " . $lead['full_name'] . ", this is PR Marketing Ventures regarding your inquiry on " . ($lead['tool_used'] ?: 'our portal') . ". How can we assist your business growth today?") ?>" target="_blank" class="cream-btn" style="background: #eafaf1; color: #065f46; border-color: #a7f3d0; padding: 0.25rem 0.5rem; font-size: 0.6875rem; font-weight: 800;">
                                        💬 WhatsApp
                                    </a>
                                    <a href="tel:<?= htmlspecialchars($rawPhone) ?>" class="cream-btn" style="padding: 0.25rem 0.5rem; font-size: 0.6875rem;">
                                        📞 Call
                                    </a>
                                </div>
                            </td>

                            <!-- 3. Website / Domain -->
                            <td style="max-width: 180px;">
                                <?php if ($cleanUrl && $lead['website_url'] !== 'N/A'): ?>
                                    <a href="<?= htmlspecialchars($cleanUrl) ?>" target="_blank" style="font-size: 0.75rem; color: var(--brown-primary); font-weight: 700; display: inline-flex; align-items: center; gap: 0.25rem; text-decoration: underline; text-underline-offset: 2px;">
                                        <span><?= htmlspecialchars(parse_url($cleanUrl, PHP_URL_HOST) ?: $lead['website_url']) ?></span>
                                        <span style="font-size: 0.6875rem;">↗</span>
                                    </a>
                                <?php else: ?>
                                    <span style="font-size: 0.75rem; color: var(--text-dim);">N/A</span>
                                <?php endif; ?>
                            </td>

                            <!-- 4. Business Category / Stage -->
                            <td style="max-width: 180px;">
                                <span class="badge-brown" style="font-size: 0.6875rem; display: inline-block; white-space: normal; line-height: 1.3;">
                                    <?= htmlspecialchars($lead['business_stage'] ?: 'Standard') ?>
                                </span>
                            </td>

                            <!-- 5. Source Tool -->
                            <td style="white-space: nowrap;">
                                <span style="display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.6rem; border-radius: 0.5rem; background: #eee5d8; font-size: 0.6875rem; font-weight: 800; color: var(--text-main);">
                                    🛠️ <?= htmlspecialchars($lead['tool_used'] ?: 'Tools Portal') ?>
                                </span>
                            </td>

                            <!-- 6. Status Selector -->
                            <td style="white-space: nowrap;">
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="update_status" value="1">
                                    <input type="hidden" name="lead_id" value="<?= htmlspecialchars($lead['id']) ?>">
                                    <select name="status" onchange="this.form.submit()" class="form-select" style="font-size: 0.6875rem; font-weight: 800; padding: 0.3rem 0.5rem; width: auto; <?= ($lead['status'] === 'Converted') ? 'background: #eafaf1; color: #065f46; border-color: #a7f3d0;' : (($lead['status'] === 'In Discussion') ? 'background: #fef3c7; color: #92400e; border-color: #fde68a;' : '') ?>">
                                        <option value="New Lead" <?= ($lead['status'] === 'New Lead') ? 'selected' : '' ?>>● New Lead</option>
                                        <option value="In Discussion" <?= ($lead['status'] === 'In Discussion') ? 'selected' : '' ?>>💬 In Discussion</option>
                                        <option value="Follow-up" <?= ($lead['status'] === 'Follow-up') ? 'selected' : '' ?>>⏱ Follow-up</option>
                                        <option value="Converted" <?= ($lead['status'] === 'Converted') ? 'selected' : '' ?>>✓ Converted</option>
                                    </select>
                                </form>
                            </td>

                            <!-- 7. Captured Date -->
                            <td style="white-space: nowrap; font-size: 0.6875rem; color: var(--text-muted);">
                                <?= date('d M Y, h:i A', strtotime($lead['created_at'])) ?>
                            </td>

                            <!-- 8. Actions -->
                            <td style="text-align: right; white-space: nowrap;">
                                <a href="/admin/leads.php?action=delete&id=<?= htmlspecialchars($lead['id']) ?>" onclick="return confirm('Are you sure you want to delete this client lead?');" class="cream-btn" style="color: var(--red-text); border-color: var(--red-border); padding: 0.35rem 0.65rem; font-size: 0.6875rem;">
                                    Delete ✕
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/layout/footer.php';
"""

with open(admin_leads_file, "w", encoding="utf-8") as f:
    f.write(content.strip())

print(f"Created {admin_leads_file} successfully!")
