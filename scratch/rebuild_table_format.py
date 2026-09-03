import os

leads_file = r"C:\hk\prmarketing\backend\admin\leads.php"

leads_code = """<?php
/**
 * PR Marketing Ventures — Client Data & Tools Inquiries Intelligence Panel
 * Premium Spacious Table Layout (Zero Horizontal Scroll, Crisp Formatting)
 */
$pageTitle = "Client Data & Leads";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/auth_middleware.php';
requireAdminAuth();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../repositories/LeadRepository.php';

$leadRepo = new LeadRepository();
$msg = '';
$error = '';

// 1. Handle CSV Export
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    $leads = $leadRepo->getAll(10000, 0);
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="PR_Marketing_Client_Leads_' . date('Y-m-d_His') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    fputs($output, "\\xEF\\xBB\\xBF"); // UTF-8 BOM
    
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
            $l['business_name'] ?: 'Not Specified',
            $l['website_url'] ?: 'Not Specified',
            $l['business_stage'] ?: 'General',
            $l['tool_used'] ?: 'Tools Inquiries',
            $l['status'] ?: 'New Lead',
            $l['notes'] ?: '',
            $l['ip_address'] ?: '',
            $l['created_at']
        ]);
    }
    
    fclose($output);
    exit;
}

// 2. Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $leadId = trim($_POST['lead_id'] ?? '');
    $newStatus = trim($_POST['status'] ?? 'New Lead');
    $notes = trim($_POST['notes'] ?? '');
    
    if ($leadId && $leadRepo->updateStatus($leadId, $newStatus, $notes)) {
        $msg = "Lead status updated to {$newStatus}.";
    } else {
        $error = "Failed to update lead status.";
    }
}

// 3. Handle Single Delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $leadId = trim($_GET['id']);
    if ($leadRepo->delete($leadId)) {
        $msg = "Lead record successfully removed.";
    } else {
        $error = "Failed to delete lead.";
    }
}

// 4. Fetch Statistics & Leads
$stats = $leadRepo->getStats();
$toolFilter = $_GET['tool'] ?? null;
$statusFilter = $_GET['status'] ?? null;
$searchQuery = $_GET['search'] ?? null;

$leads = $leadRepo->getAll(200, 0, $toolFilter, $statusFilter, $searchQuery);

require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/layout/sidebar.php';
?>

<!-- 1. Header Banner & Action Bar -->
<div style="background: linear-gradient(135deg, #f7f1e7 0%, #ebe0cf 100%); border-radius: 1.25rem; padding: 1.75rem 2rem; border: 1px solid var(--border-medium); box-shadow: 0 4px 20px rgba(80, 50, 20, 0.04); display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1.25rem;">
    <div style="display: flex; flex-direction: column; gap: 0.35rem;">
        <div style="display: inline-flex; align-items: center; gap: 0.45rem; padding: 0.25rem 0.75rem; border-radius: 9999px; background: #ffffff; border: 1px solid var(--border-medium); font-size: 0.7rem; font-weight: 800; color: var(--brown-primary); text-transform: uppercase; letter-spacing: 0.06em; width: fit-content; box-shadow: 0 2px 8px rgba(80, 50, 20, 0.04);">
            <span>👥</span> Real-Time Client Leads
        </div>
        <h1 style="font-size: 1.75rem; font-weight: 900; color: var(--text-main); font-family: 'Space Grotesk', sans-serif; letter-spacing: -0.02em;">
            Client Data & Inquiries
        </h1>
        <p style="font-size: 0.8125rem; color: var(--text-muted);">
            Real-time inquiries captured from Domain Authority Checker, Google Review QR, and WhatsApp Link Generator.
        </p>
    </div>

    <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
        <a href="/admin/leads.php?action=export_csv" class="gold-btn" style="padding: 0.7rem 1.25rem; font-size: 0.8125rem;">
            <span>📥</span> Export to Excel / CSV
        </a>
        <a href="/tools/" target="_blank" class="cream-btn" style="padding: 0.7rem 1.15rem; font-size: 0.8125rem; background: #ffffff;">
            <span>🛠️</span> Growth Tools Hub ↗
        </a>
    </div>
</div>

<!-- Alerts -->
<?php if ($msg): ?>
    <div style="padding: 0.875rem 1.25rem; border-radius: 0.75rem; background: var(--emerald-bg); border: 1px solid var(--emerald-border); color: var(--emerald-text); font-size: 0.8125rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
        <span>✓</span> <?= htmlspecialchars($msg) ?>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div style="padding: 0.875rem 1.25rem; border-radius: 0.75rem; background: var(--red-bg); border: 1px solid var(--red-border); color: var(--red-text); font-size: 0.8125rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
        <span>⚠</span> <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<!-- 2. KPI Cards -->
<div class="grid-4">
    <div class="glass-card">
        <p style="font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: var(--brown-primary); letter-spacing: 0.05em;">Total Client Inquiries</p>
        <p style="font-size: 2rem; font-weight: 900; font-family: 'Space Grotesk', sans-serif; color: var(--text-main); margin-top: 0.35rem;"><?= $stats['total'] ?></p>
        <p style="font-size: 0.75rem; color: var(--text-dim); margin-top: 0.25rem;">100% Real Live Leads</p>
    </div>

    <div class="glass-card">
        <p style="font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: #0369a1; letter-spacing: 0.05em;">New Leads Pending</p>
        <p style="font-size: 2rem; font-weight: 900; font-family: 'Space Grotesk', sans-serif; color: #0284c7; margin-top: 0.35rem;"><?= $stats['new'] ?></p>
        <p style="font-size: 0.75rem; color: var(--text-dim); margin-top: 0.25rem;">Awaiting WhatsApp Outreach</p>
    </div>

    <div class="glass-card">
        <p style="font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: #d97706; letter-spacing: 0.05em;">In Discussion</p>
        <p style="font-size: 2rem; font-weight: 900; font-family: 'Space Grotesk', sans-serif; color: #b45309; margin-top: 0.35rem;"><?= $stats['in_discussion'] ?></p>
        <p style="font-size: 0.75rem; color: var(--text-dim); margin-top: 0.25rem;">Active Client Conversations</p>
    </div>

    <div class="glass-card">
        <p style="font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: #15803d; letter-spacing: 0.05em;">Converted Clients</p>
        <p style="font-size: 2rem; font-weight: 900; font-family: 'Space Grotesk', sans-serif; color: #16a34a; margin-top: 0.35rem;"><?= $stats['converted'] ?></p>
        <p style="font-size: 0.75rem; color: var(--text-dim); margin-top: 0.25rem;">Successfully Closed Deals</p>
    </div>
</div>

<!-- 3. Search & Filter Bar -->
<div class="glass-card" style="padding: 1.25rem 1.5rem;">
    <form method="GET" style="display: flex; flex-wrap: wrap; gap: 0.85rem; align-items: center;">
        
        <div style="flex: 2; min-width: 250px;">
            <input type="text" name="search" value="<?= htmlspecialchars($searchQuery ?? '') ?>" placeholder="🔍 Search by Client Name, Phone, Brand, Domain..." class="form-input" style="padding: 0.7rem 1rem; font-size: 0.8125rem;">
        </div>
        
        <div style="flex: 1; min-width: 150px;">
            <select name="status" class="form-select" style="padding: 0.7rem 1rem; font-size: 0.8125rem;">
                <option value="">All Lead Statuses</option>
                <option value="New Lead" <?= $statusFilter === 'New Lead' ? 'selected' : '' ?>>New Lead</option>
                <option value="In Discussion" <?= $statusFilter === 'In Discussion' ? 'selected' : '' ?>>In Discussion</option>
                <option value="Follow-up" <?= $statusFilter === 'Follow-up' ? 'selected' : '' ?>>Follow-up</option>
                <option value="Converted" <?= $statusFilter === 'Converted' ? 'selected' : '' ?>>Converted</option>
            </select>
        </div>

        <div style="flex: 1; min-width: 160px;">
            <select name="tool" class="form-select" style="padding: 0.7rem 1rem; font-size: 0.8125rem;">
                <option value="">All Source Tools</option>
                <option value="Domain Authority Checker" <?= $toolFilter === 'Domain Authority Checker' ? 'selected' : '' ?>>Domain Authority</option>
                <option value="Google Review QR Generator" <?= $toolFilter === 'Google Review QR Generator' ? 'selected' : '' ?>>Google Review QR</option>
                <option value="WhatsApp Link Generator" <?= $toolFilter === 'WhatsApp Link Generator' ? 'selected' : '' ?>>WhatsApp Link Gen</option>
            </select>
        </div>

        <div style="display: flex; gap: 0.5rem;">
            <button type="submit" class="gold-btn" style="padding: 0.7rem 1.25rem; font-size: 0.8125rem;">
                Filter
            </button>
            <?php if ($searchQuery || $statusFilter || $toolFilter): ?>
                <a href="/admin/leads.php" class="cream-btn" style="padding: 0.7rem 1rem; font-size: 0.8125rem;">
                    Clear
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- 4. Beautiful, Spacious Premium Table (Fixed Proportions, Zero Squishing) -->
<div class="admin-table-container">
    <?php if (empty($leads)): ?>
        <div style="padding: 4.5rem 2rem; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 1.15rem;">
            <div style="width: 4rem; height: 4rem; border-radius: 1.15rem; background: #f5ede2; border: 1px solid var(--border-medium); display: flex; align-items: center; justify-content: center; font-size: 2rem;">
                👥
            </div>
            <div>
                <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--text-main); font-family: 'Space Grotesk', sans-serif;">
                    No Client Inquiries in Database Yet
                </h3>
                <p style="font-size: 0.875rem; color: var(--text-muted); max-width: 34rem; margin: 0.4rem auto 0; line-height: 1.6;">
                    Inquiries submitted by real clients on Domain Authority Checker, Google Review QR, or WhatsApp Link Generator will appear here automatically.
                </p>
            </div>
            <div style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.45rem 1rem; border-radius: 9999px; background: #eafaf1; border: 1px solid #a7f3d0; color: #065f46; font-size: 0.8125rem; font-weight: 800;">
                <span>●</span> Database Listening Active
            </div>
        </div>
    <?php else: ?>
        <table class="admin-table" style="table-layout: fixed; width: 100%;">
            <thead>
                <tr>
                    <th style="width: 22%; padding: 1.1rem 1.25rem;">Client & Business</th>
                    <th style="width: 21%; padding: 1.1rem 1.25rem;">Contact & Outreach</th>
                    <th style="width: 20%; padding: 1.1rem 1.25rem;">Target Domain & Stage</th>
                    <th style="width: 14%; padding: 1.1rem 1.25rem;">Source Tool</th>
                    <th style="width: 13%; padding: 1.1rem 1.25rem;">Status Pipeline</th>
                    <th style="width: 10%; padding: 1.1rem 1.25rem; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($leads as $l): ?>
                    <?php 
                        $cleanPhone = preg_replace('/[^0-9]/', '', $l['phone_number']);
                        if (strlen($cleanPhone) === 10) $cleanPhone = '91' . $cleanPhone;
                        
                        $encodedMsg = urlencode("Hello " . $l['full_name'] . ", this is PR Marketing Ventures regarding your inquiry on " . $l['tool_used'] . ". How can we assist your business growth today?");
                        $waUrl = "https://wa.me/{$cleanPhone}?text={$encodedMsg}";
                    ?>
                    <tr>
                        <!-- 1. Client & Business -->
                        <td style="padding: 1.25rem 1.25rem; vertical-align: middle;">
                            <div style="font-weight: 900; color: var(--text-main); font-size: 0.95rem; line-height: 1.3;">
                                <?= htmlspecialchars($l['full_name']) ?>
                            </div>
                            <?php if (!empty($l['business_name'])): ?>
                                <div style="margin-top: 0.35rem;">
                                    <span style="display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.75rem; color: var(--brown-primary); font-weight: 800; background: #f5ede2; padding: 0.2rem 0.55rem; border-radius: 0.4rem; border: 1px solid var(--border-medium);">
                                        🏢 <?= htmlspecialchars($l['business_name']) ?>
                                    </span>
                                </div>
                            <?php else: ?>
                                <div style="font-size: 0.75rem; color: var(--text-dim); margin-top: 0.25rem;">Individual Inquiry</div>
                            <?php endif; ?>
                        </td>

                        <!-- 2. Contact & Outreach -->
                        <td style="padding: 1.25rem 1.25rem; vertical-align: middle;">
                            <div style="font-family: 'Space Grotesk', sans-serif; font-weight: 900; color: var(--text-main); font-size: 0.9rem; letter-spacing: 0.02em;">
                                +91 <?= htmlspecialchars($l['phone_number']) ?>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.45rem; margin-top: 0.45rem;">
                                <a href="<?= $waUrl ?>" target="_blank" style="display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.35rem 0.75rem; border-radius: 0.5rem; background: linear-gradient(135deg, #25d366 0%, #128c7e 100%); color: #ffffff; font-size: 0.75rem; font-weight: 900; text-decoration: none; white-space: nowrap; box-shadow: 0 2px 8px rgba(37, 211, 102, 0.25);">
                                    💬 WhatsApp
                                </a>
                                <a href="tel:<?= htmlspecialchars($l['phone_number']) ?>" style="display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.35rem 0.65rem; border-radius: 0.5rem; background: #ffffff; color: var(--text-main); font-size: 0.75rem; font-weight: 700; text-decoration: none; border: 1px solid var(--border-medium); white-space: nowrap;">
                                    📞 Call
                                </a>
                            </div>
                        </td>

                        <!-- 3. Target Domain & Stage -->
                        <td style="padding: 1.25rem 1.25rem; vertical-align: middle;">
                            <?php if (!empty($l['website_url'])): ?>
                                <?php 
                                    $url = $l['website_url'];
                                    if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                                        $url = 'https://' . $url;
                                    }
                                ?>
                                <div>
                                    <a href="<?= htmlspecialchars($url) ?>" target="_blank" style="font-weight: 800; color: #2563eb; text-decoration: underline; font-size: 0.8125rem; display: inline-flex; align-items: center; gap: 0.25rem; word-break: break-all;">
                                        <?= htmlspecialchars(parse_url($url, PHP_URL_HOST) ?: $l['website_url']) ?> ↗
                                    </a>
                                </div>
                            <?php endif; ?>
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.35rem; font-weight: 600;">
                                🌱 <?= htmlspecialchars($l['business_stage'] ?: 'Not Specified') ?>
                            </div>
                        </td>

                        <!-- 4. Source Tool -->
                        <td style="padding: 1.25rem 1.25rem; vertical-align: middle;">
                            <span style="display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.35rem 0.65rem; border-radius: 0.5rem; background: #fbf5ee; border: 1px solid var(--border-medium); font-size: 0.75rem; font-weight: 700; color: var(--brown-dark); line-height: 1.3;">
                                🛠️ <?= htmlspecialchars($l['tool_used']) ?>
                            </span>
                        </td>

                        <!-- 5. Status Pipeline Dropdown -->
                        <td style="padding: 1.25rem 1.25rem; vertical-align: middle;">
                            <form method="POST" style="margin: 0;">
                                <input type="hidden" name="update_status" value="1">
                                <input type="hidden" name="lead_id" value="<?= htmlspecialchars($l['id']) ?>">
                                <select name="status" onchange="this.form.submit()" class="form-select" style="padding: 0.45rem 0.75rem; font-size: 0.75rem; font-weight: 800; border-radius: 0.55rem; width: 100%; background-color: #faf7f2; border: 1px solid var(--border-medium); cursor: pointer;">
                                    <option value="New Lead" <?= $l['status'] === 'New Lead' ? 'selected' : '' ?>>🔵 New Lead</option>
                                    <option value="In Discussion" <?= $l['status'] === 'In Discussion' ? 'selected' : '' ?>>🟡 In Discussion</option>
                                    <option value="Follow-up" <?= $l['status'] === 'Follow-up' ? 'selected' : '' ?>>🟣 Follow-up</option>
                                    <option value="Converted" <?= $l['status'] === 'Converted' ? 'selected' : '' ?>>🟢 Converted</option>
                                </select>
                            </form>
                            <div style="font-size: 0.6875rem; color: var(--text-dim); margin-top: 0.35rem;">
                                <?= date('d M, h:i A', strtotime($l['created_at'])) ?>
                            </div>
                        </td>

                        <!-- 6. Delete Action -->
                        <td style="padding: 1.25rem 1.25rem; vertical-align: middle; text-align: right;">
                            <a href="/admin/leads.php?action=delete&id=<?= urlencode($l['id']) ?>" onclick="return confirm('Delete this inquiry record?')" style="display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.35rem 0.65rem; border-radius: 0.5rem; background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; font-size: 0.75rem; font-weight: 800; text-decoration: none; transition: all 0.2s;">
                                ✕ Delete
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
"""

with open(leads_file, "w", encoding="utf-8") as f:
    f.write(leads_code.strip())

print(f"Updated {leads_file} with fixed-proportion spacious table format!")
