import os

leads_file = r"C:\hk\prmarketing\backend\admin\leads.php"

leads_code = """<?php
/**
 * PR Marketing Ventures — Luxury Client Leads Intelligence Feed & CRM
 * Spacious Luxury Card & Table Suite (Zero Clutter, 100% Breathing Room)
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
$viewMode = $_GET['view'] ?? 'cards'; // Default to spacious Luxury Cards

$leads = $leadRepo->getAll(200, 0, $toolFilter, $statusFilter, $searchQuery);

require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/layout/sidebar.php';
?>

<!-- 1. Header Banner & Action Bar -->
<div style="background: linear-gradient(135deg, #f7f1e7 0%, #ebe0cf 100%); border-radius: 1.25rem; padding: 2rem 2.25rem; border: 1px solid var(--border-medium); box-shadow: 0 4px 20px rgba(80, 50, 20, 0.04); display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1.5rem;">
    <div style="display: flex; flex-direction: column; gap: 0.45rem;">
        <div style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.3rem 0.85rem; border-radius: 9999px; background: #ffffff; border: 1px solid var(--border-medium); font-size: 0.7rem; font-weight: 800; color: var(--brown-primary); text-transform: uppercase; letter-spacing: 0.06em; width: fit-content; box-shadow: 0 2px 8px rgba(80, 50, 20, 0.04);">
            <span>👥</span> Real-Time Client Lead Engine
        </div>
        <h1 style="font-size: 1.85rem; font-weight: 900; color: var(--text-main); font-family: 'Space Grotesk', sans-serif; letter-spacing: -0.02em;">
            Client Inquiries & CRM Suite
        </h1>
        <p style="font-size: 0.875rem; color: var(--text-muted); line-height: 1.5;">
            Manage incoming high-intent client inquiries from tools with 1-click WhatsApp outreach, status pipelines, and real-time CRM syncing.
        </p>
    </div>

    <div style="display: flex; align-items: center; gap: 0.875rem; flex-wrap: wrap;">
        <a href="/admin/leads.php?action=export_csv" class="gold-btn" style="padding: 0.75rem 1.35rem; font-size: 0.8125rem;">
            <span>📥</span> Export to Excel / CSV
        </a>
        <a href="/tools/" target="_blank" class="cream-btn" style="padding: 0.75rem 1.25rem; font-size: 0.8125rem; background: #ffffff;">
            <span>🛠️</span> Growth Tools Hub ↗
        </a>
    </div>
</div>

<!-- Alerts -->
<?php if ($msg): ?>
    <div style="padding: 1rem 1.35rem; border-radius: 0.85rem; background: var(--emerald-bg); border: 1px solid var(--emerald-border); color: var(--emerald-text); font-size: 0.875rem; font-weight: 700; display: flex; align-items: center; gap: 0.6rem; box-shadow: 0 2px 10px rgba(6, 95, 70, 0.05);">
        <span style="font-size: 1.1rem;">✓</span> <?= htmlspecialchars($msg) ?>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div style="padding: 1rem 1.35rem; border-radius: 0.85rem; background: var(--red-bg); border: 1px solid var(--red-border); color: var(--red-text); font-size: 0.875rem; font-weight: 700; display: flex; align-items: center; gap: 0.6rem; box-shadow: 0 2px 10px rgba(153, 27, 27, 0.05);">
        <span style="font-size: 1.1rem;">⚠</span> <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<!-- 2. High-Impact KPI Cards -->
<div class="grid-4">
    <div class="glass-card" style="position: relative; overflow: hidden;">
        <div style="position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, #a0683b, #dcc8b4);"></div>
        <p style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: var(--brown-primary); letter-spacing: 0.06em;">Total Inquiries</p>
        <p style="font-size: 2.25rem; font-weight: 900; font-family: 'Space Grotesk', sans-serif; color: var(--text-main); margin-top: 0.5rem; line-height: 1;"><?= $stats['total'] ?></p>
        <div style="display: flex; align-items: center; gap: 0.35rem; margin-top: 0.6rem; font-size: 0.75rem;">
            <span style="color: #059669; font-weight: 800;">● Active</span>
            <span style="color: var(--text-dim);">real database leads</span>
        </div>
    </div>

    <div class="glass-card" style="position: relative; overflow: hidden;">
        <div style="position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, #0284c7, #7dd3fc);"></div>
        <p style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #0369a1; letter-spacing: 0.06em;">New Leads Pending</p>
        <p style="font-size: 2.25rem; font-weight: 900; font-family: 'Space Grotesk', sans-serif; color: #0284c7; margin-top: 0.5rem; line-height: 1;"><?= $stats['new'] ?></p>
        <div style="display: flex; align-items: center; gap: 0.35rem; margin-top: 0.6rem; font-size: 0.75rem;">
            <span style="color: #0284c7; font-weight: 800;">● Action Required</span>
            <span style="color: var(--text-dim);">awaiting outreach</span>
        </div>
    </div>

    <div class="glass-card" style="position: relative; overflow: hidden;">
        <div style="position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, #d97706, #fde68a);"></div>
        <p style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #b45309; letter-spacing: 0.06em;">In Discussion</p>
        <p style="font-size: 2.25rem; font-weight: 900; font-family: 'Space Grotesk', sans-serif; color: #b45309; margin-top: 0.5rem; line-height: 1;"><?= $stats['in_discussion'] ?></p>
        <div style="display: flex; align-items: center; gap: 0.35rem; margin-top: 0.6rem; font-size: 0.75rem;">
            <span style="color: #d97706; font-weight: 800;">● In Progress</span>
            <span style="color: var(--text-dim);">client discussion</span>
        </div>
    </div>

    <div class="glass-card" style="position: relative; overflow: hidden;">
        <div style="position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, #16a34a, #86efac);"></div>
        <p style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #15803d; letter-spacing: 0.06em;">Converted Clients</p>
        <p style="font-size: 2.25rem; font-weight: 900; font-family: 'Space Grotesk', sans-serif; color: #16a34a; margin-top: 0.5rem; line-height: 1;"><?= $stats['converted'] ?></p>
        <div style="display: flex; align-items: center; gap: 0.35rem; margin-top: 0.6rem; font-size: 0.75rem;">
            <span style="color: #15803d; font-weight: 800;">● Won</span>
            <span style="color: var(--text-dim);">successfully closed</span>
        </div>
    </div>
</div>

<!-- 3. Search, Filter & View Controls -->
<div class="glass-card" style="padding: 1.25rem 1.5rem;">
    <form method="GET" style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: center; justify-content: space-between;">
        
        <!-- Search Input -->
        <div style="flex: 2; min-width: 260px;">
            <input type="text" name="search" value="<?= htmlspecialchars($searchQuery ?? '') ?>" placeholder="🔍 Search by Client Name, Phone, Brand, Domain..." class="form-input" style="padding: 0.75rem 1.15rem; font-size: 0.875rem;">
        </div>
        
        <!-- Status Filter -->
        <div style="flex: 1; min-width: 160px;">
            <select name="status" class="form-select" style="padding: 0.75rem 1rem; font-size: 0.8125rem;">
                <option value="">All Statuses</option>
                <option value="New Lead" <?= $statusFilter === 'New Lead' ? 'selected' : '' ?>>New Lead</option>
                <option value="In Discussion" <?= $statusFilter === 'In Discussion' ? 'selected' : '' ?>>In Discussion</option>
                <option value="Follow-up" <?= $statusFilter === 'Follow-up' ? 'selected' : '' ?>>Follow-up</option>
                <option value="Converted" <?= $statusFilter === 'Converted' ? 'selected' : '' ?>>Converted</option>
            </select>
        </div>

        <!-- Tool Filter -->
        <div style="flex: 1; min-width: 170px;">
            <select name="tool" class="form-select" style="padding: 0.75rem 1rem; font-size: 0.8125rem;">
                <option value="">All Source Tools</option>
                <option value="Domain Authority Checker" <?= $toolFilter === 'Domain Authority Checker' ? 'selected' : '' ?>>Domain Authority</option>
                <option value="Google Review QR Generator" <?= $toolFilter === 'Google Review QR Generator' ? 'selected' : '' ?>>Google Review QR</option>
                <option value="WhatsApp Link Generator" <?= $toolFilter === 'WhatsApp Link Generator' ? 'selected' : '' ?>>WhatsApp Link Gen</option>
            </select>
        </div>

        <input type="hidden" name="view" value="<?= htmlspecialchars($viewMode) ?>">

        <!-- Actions -->
        <div style="display: flex; align-items: center; gap: 0.65rem;">
            <button type="submit" class="gold-btn" style="padding: 0.75rem 1.25rem; font-size: 0.8125rem;">
                Filter
            </button>
            <?php if ($searchQuery || $statusFilter || $toolFilter): ?>
                <a href="/admin/leads.php?view=<?= urlencode($viewMode) ?>" class="cream-btn" style="padding: 0.75rem 1.15rem; font-size: 0.8125rem;">
                    Clear
                </a>
            <?php endif; ?>

            <!-- View Mode Switcher -->
            <div style="display: inline-flex; border: 1px solid var(--border-medium); border-radius: 0.65rem; padding: 0.2rem; background: #faf7f2; margin-left: 0.5rem;">
                <a href="?view=cards<?= $searchQuery ? '&search=' . urlencode($searchQuery) : '' ?><?= $statusFilter ? '&status=' . urlencode($statusFilter) : '' ?><?= $toolFilter ? '&tool=' . urlencode($toolFilter) : '' ?>" style="padding: 0.45rem 0.85rem; border-radius: 0.5rem; font-size: 0.75rem; font-weight: 800; text-decoration: none; display: inline-flex; align-items: center; gap: 0.35rem; <?= $viewMode === 'cards' ? 'background: #8c5835; color: #ffffff;' : 'color: var(--text-muted);' ?>">
                    <span>🗂️</span> Cards View
                </a>
                <a href="?view=table<?= $searchQuery ? '&search=' . urlencode($searchQuery) : '' ?><?= $statusFilter ? '&status=' . urlencode($statusFilter) : '' ?><?= $toolFilter ? '&tool=' . urlencode($toolFilter) : '' ?>" style="padding: 0.45rem 0.85rem; border-radius: 0.5rem; font-size: 0.75rem; font-weight: 800; text-decoration: none; display: inline-flex; align-items: center; gap: 0.35rem; <?= $viewMode === 'table' ? 'background: #8c5835; color: #ffffff;' : 'color: var(--text-muted);' ?>">
                    <span>📋</span> Table View
                </a>
            </div>
        </div>
    </form>
</div>

<!-- 4. Client Leads Feed (Zero "Ghich Ghich" — Spacious Luxury Design) -->
<?php if (empty($leads)): ?>
    <div class="glass-card" style="padding: 4.5rem 2rem; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 1.15rem;">
        <div style="width: 4rem; height: 4rem; border-radius: 1.15rem; background: #f5ede2; border: 1px solid var(--border-medium); display: flex; align-items: center; justify-content: center; font-size: 2rem; box-shadow: 0 4px 15px rgba(80, 50, 20, 0.05);">
            👥
        </div>
        <div>
            <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--text-main); font-family: 'Space Grotesk', sans-serif;">
                No Client Inquiries in Database Yet
            </h3>
            <p style="font-size: 0.875rem; color: var(--text-muted); max-width: 34rem; margin: 0.4rem auto 0; line-height: 1.6;">
                Incoming inquiries submitted by real visitors on Domain Authority Checker, Google Review QR, or WhatsApp Link Generator will instantly appear here with 1-click WhatsApp outreach.
            </p>
        </div>
        <div style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.45rem 1rem; border-radius: 9999px; background: #eafaf1; border: 1px solid #a7f3d0; color: #065f46; font-size: 0.8125rem; font-weight: 800;">
            <span style="font-size: 0.65rem;">●</span> Database Listening Active
        </div>
    </div>

<?php elseif ($viewMode === 'table'): ?>
    <!-- SPACIOUS TABLE VIEW -->
    <div class="admin-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="padding: 1rem 1.25rem;">Client Details</th>
                    <th style="padding: 1rem 1.25rem;">Direct Outreach</th>
                    <th style="padding: 1rem 1.25rem;">Website & Stage</th>
                    <th style="padding: 1rem 1.25rem;">Tool Inquired</th>
                    <th style="padding: 1rem 1.25rem;">Pipeline Status</th>
                    <th style="padding: 1rem 1.25rem; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($leads as $l): ?>
                    <?php
                        $cleanPhone = preg_replace('/[^0-9]/', '', $l['phone_number']);
                        if (strlen($cleanPhone) === 10) $cleanPhone = '91' . $cleanPhone;
                        $waMsg = urlencode("Hello " . $l['full_name'] . ", this is PR Marketing Ventures regarding your inquiry on " . $l['tool_used'] . ". How can we assist your business growth today?");
                        $waUrl = "https://wa.me/{$cleanPhone}?text={$waMsg}";
                    ?>
                    <tr>
                        <td style="padding: 1.25rem;">
                            <div style="font-weight: 900; color: var(--text-main); font-size: 0.9375rem;"><?= htmlspecialchars($l['full_name']) ?></div>
                            <?php if ($l['business_name']): ?>
                                <div style="font-size: 0.8125rem; color: var(--brown-primary); font-weight: 700; margin-top: 0.25rem;">🏢 <?= htmlspecialchars($l['business_name']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 1.25rem;">
                            <div style="font-weight: 800; font-family: 'Space Grotesk', sans-serif;"><?= htmlspecialchars($l['phone_number']) ?></div>
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.4rem;">
                                <a href="<?= $waUrl ?>" target="_blank" style="display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.35rem 0.75rem; border-radius: 0.5rem; background: #25d366; color: #ffffff; font-size: 0.75rem; font-weight: 800; text-decoration: none; white-space: nowrap;">
                                    💬 WhatsApp
                                </a>
                                <a href="tel:<?= htmlspecialchars($l['phone_number']) ?>" style="display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.35rem 0.65rem; border-radius: 0.5rem; background: #f5ede2; color: var(--text-main); font-size: 0.75rem; font-weight: 700; text-decoration: none; border: 1px solid var(--border-medium); white-space: nowrap;">
                                    📞 Call
                                </a>
                            </div>
                        </td>
                        <td style="padding: 1.25rem;">
                            <?php if ($l['website_url']): ?>
                                <?php 
                                    $u = $l['website_url'];
                                    if (!str_starts_with($u, 'http://') && !str_starts_with($u, 'https://')) $u = 'https://' . $u;
                                ?>
                                <a href="<?= htmlspecialchars($u) ?>" target="_blank" style="color: #2563eb; font-weight: 700; text-decoration: underline; font-size: 0.8125rem;">
                                    <?= htmlspecialchars(parse_url($u, PHP_URL_HOST) ?: $l['website_url']) ?> ↗
                                </a>
                            <?php endif; ?>
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.2rem;"><?= htmlspecialchars($l['business_stage'] ?: 'General') ?></div>
                        </td>
                        <td style="padding: 1.25rem;">
                            <span style="display: inline-flex; padding: 0.35rem 0.75rem; border-radius: 0.6rem; background: #fbf5ee; border: 1px solid var(--border-medium); font-size: 0.75rem; font-weight: 700; color: var(--brown-dark);">
                                🛠️ <?= htmlspecialchars($l['tool_used']) ?>
                            </span>
                        </td>
                        <td style="padding: 1.25rem;">
                            <form method="POST">
                                <input type="hidden" name="update_status" value="1">
                                <input type="hidden" name="lead_id" value="<?= htmlspecialchars($l['id']) ?>">
                                <select name="status" onchange="this.form.submit()" class="form-select" style="padding: 0.4rem 0.75rem; font-size: 0.8125rem; font-weight: 700; border-radius: 0.6rem; width: auto; background-color: #faf7f2;">
                                    <option value="New Lead" <?= $l['status'] === 'New Lead' ? 'selected' : '' ?>>● New Lead</option>
                                    <option value="In Discussion" <?= $l['status'] === 'In Discussion' ? 'selected' : '' ?>>💬 In Discussion</option>
                                    <option value="Follow-up" <?= $l['status'] === 'Follow-up' ? 'selected' : '' ?>>⏱ Follow-up</option>
                                    <option value="Converted" <?= $l['status'] === 'Converted' ? 'selected' : '' ?>>✓ Converted</option>
                                </select>
                            </form>
                        </td>
                        <td style="padding: 1.25rem; text-align: right;">
                            <div style="font-size: 0.75rem; color: var(--text-muted);"><?= date('d M Y, h:i A', strtotime($l['created_at'])) ?></div>
                            <div style="margin-top: 0.4rem;">
                                <a href="/admin/leads.php?action=delete&id=<?= urlencode($l['id']) ?>" onclick="return confirm('Delete this inquiry record?')" style="font-size: 0.75rem; font-weight: 700; color: #dc2626; text-decoration: none;">
                                    Delete ✕
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php else: ?>
    <!-- LUXURY EXPANDED CRM CARDS VIEW (CLEAN, SPACIOUS & ZERO CLUTTER) -->
    <div style="display: flex; flex-direction: column; gap: 1.25rem;">
        <?php foreach ($leads as $l): ?>
            <?php
                $cleanPhone = preg_replace('/[^0-9]/', '', $l['phone_number']);
                if (strlen($cleanPhone) === 10) $cleanPhone = '91' . $cleanPhone;
                $waMsg = urlencode("Hello " . $l['full_name'] . ", this is PR Marketing Ventures regarding your inquiry on " . $l['tool_used'] . ". How can we assist your business growth today?");
                $waUrl = "https://wa.me/{$cleanPhone}?text={$waMsg}";

                // Initials for luxury avatar
                $parts = explode(' ', trim($l['full_name']));
                $initials = strtoupper(substr($parts[0] ?? 'C', 0, 1) . substr($parts[1] ?? '', 0, 1));
                if (empty($initials)) $initials = 'PR';

                $statusBadgeClass = 'status-new';
                if ($l['status'] === 'In Discussion') $statusBadgeClass = 'status-discussion';
                elseif ($l['status'] === 'Follow-up') $statusBadgeClass = 'status-followup';
                elseif ($l['status'] === 'Converted') $statusBadgeClass = 'status-converted';
            ?>
            <div class="glass-card" style="padding: 1.75rem 2rem; background: #ffffff; border: 1px solid var(--border-medium); border-radius: 1.25rem; box-shadow: 0 4px 20px rgba(80, 50, 20, 0.04); transition: all 0.2s ease;">
                
                <!-- Card Header (Identity & Actions) -->
                <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; border-bottom: 1px solid var(--border-subtle); padding-bottom: 1.25rem;">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 3.25rem; height: 3.25rem; border-radius: 0.875rem; background: linear-gradient(135deg, #a0683b 0%, #7d4a22 100%); display: flex; align-items: center; justify-content: center; color: #ffffff; font-weight: 900; font-size: 1.15rem; font-family: 'Space Grotesk', sans-serif; box-shadow: 0 4px 12px rgba(140, 88, 53, 0.25); border: 1px solid rgba(255, 255, 255, 0.3); flex-shrink: 0;">
                            <?= $initials ?>
                        </div>
                        <div>
                            <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.6rem;">
                                <h3 style="font-size: 1.2rem; font-weight: 900; color: var(--text-main); font-family: 'Space Grotesk', sans-serif; letter-spacing: -0.01em;">
                                    <?= htmlspecialchars($l['full_name']) ?>
                                </h3>
                                <?php if (!empty($l['business_name'])): ?>
                                    <span style="display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.25rem 0.65rem; border-radius: 9999px; background: #f5ede2; border: 1px solid var(--border-medium); font-size: 0.75rem; font-weight: 800; color: var(--brown-primary);">
                                        🏢 <?= htmlspecialchars($l['business_name']) ?>
                                    </span>
                                <?php endif; ?>
                                <span style="display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.25rem 0.65rem; border-radius: 9999px; background: #fbf5ee; border: 1px solid var(--border-medium); font-size: 0.75rem; font-weight: 700; color: var(--text-muted);">
                                    🌱 <?= htmlspecialchars($l['business_stage'] ?: 'Startup / Enterprise') ?>
                                </span>
                            </div>
                            <div style="font-size: 0.75rem; color: var(--text-dim); margin-top: 0.35rem;">
                                Inquired on <strong><?= htmlspecialchars($l['tool_used']) ?></strong> • Received <?= date('d M Y, h:i A', strtotime($l['created_at'])) ?>
                            </div>
                        </div>
                    </div>

                    <!-- Right Quick Delete -->
                    <div>
                        <a href="/admin/leads.php?action=delete&id=<?= urlencode($l['id']) ?>&view=<?= urlencode($viewMode) ?>" onclick="return confirm('Are you sure you want to remove this lead record?')" style="display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.45rem 0.85rem; border-radius: 0.65rem; background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; font-size: 0.75rem; font-weight: 800; text-decoration: none; transition: all 0.2s;">
                            <span>🗑️</span> Delete Lead
                        </a>
                    </div>
                </div>

                <!-- Card Body (3 Generous Columns with Full Breathing Room) -->
                <div style="display: grid; grid-template-columns: 1.2fr 1.2fr 1fr; gap: 2rem; margin-top: 1.5rem; align-items: center;">
                    
                    <!-- Col 1: Direct Contact Outreach -->
                    <div style="background: #faf7f2; border: 1px solid var(--border-subtle); border-radius: 0.875rem; padding: 1rem 1.25rem;">
                        <div style="font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: var(--brown-primary); margin-bottom: 0.35rem;">
                            📞 Phone & Instant Outreach
                        </div>
                        <div style="font-size: 1.05rem; font-weight: 900; font-family: 'Space Grotesk', sans-serif; color: var(--text-main); letter-spacing: 0.02em;">
                            +91 <?= htmlspecialchars($l['phone_number']) ?>
                        </div>
                        <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; margin-top: 0.65rem;">
                            <a href="<?= $waUrl ?>" target="_blank" style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.45rem 0.95rem; border-radius: 0.65rem; background: linear-gradient(135deg, #25d366 0%, #128c7e 100%); color: #ffffff; font-size: 0.75rem; font-weight: 900; text-decoration: none; box-shadow: 0 3px 10px rgba(37, 211, 102, 0.25); white-space: nowrap;">
                                <span style="font-size: 0.9rem;">💬</span> WhatsApp Chat →
                            </a>
                            <a href="tel:<?= htmlspecialchars($l['phone_number']) ?>" style="display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.45rem 0.85rem; border-radius: 0.65rem; background: #ffffff; color: var(--text-main); font-size: 0.75rem; font-weight: 800; text-decoration: none; border: 1px solid var(--border-medium); white-space: nowrap;">
                                <span>📞</span> Call
                            </a>
                        </div>
                    </div>

                    <!-- Col 2: Target Domain & Source Tool -->
                    <div style="background: #faf7f2; border: 1px solid var(--border-subtle); border-radius: 0.875rem; padding: 1rem 1.25rem;">
                        <div style="font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: var(--brown-primary); margin-bottom: 0.35rem;">
                            🌐 Website & Source Tool
                        </div>
                        <div>
                            <?php if (!empty($l['website_url'])): ?>
                                <?php 
                                    $targetUrl = $l['website_url'];
                                    if (!str_starts_with($targetUrl, 'http://') && !str_starts_with($targetUrl, 'https://')) {
                                        $targetUrl = 'https://' . $targetUrl;
                                    }
                                ?>
                                <a href="<?= htmlspecialchars($targetUrl) ?>" target="_blank" style="font-size: 0.9375rem; font-weight: 800; color: #2563eb; text-decoration: underline; display: inline-flex; align-items: center; gap: 0.3rem;">
                                    <?= htmlspecialchars(parse_url($targetUrl, PHP_URL_HOST) ?: $l['website_url']) ?> ↗
                                </a>
                            <?php else: ?>
                                <span style="font-size: 0.875rem; color: var(--text-dim); font-weight: 600;">No URL provided</span>
                            <?php endif; ?>
                        </div>
                        <div style="margin-top: 0.65rem;">
                            <span style="display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.3rem 0.7rem; border-radius: 0.5rem; background: #ffffff; border: 1px solid var(--border-medium); font-size: 0.75rem; font-weight: 800; color: var(--brown-dark);">
                                🛠️ <?= htmlspecialchars($l['tool_used']) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Col 3: Pipeline Status Selector -->
                    <div style="background: #faf7f2; border: 1px solid var(--border-subtle); border-radius: 0.875rem; padding: 1rem 1.25rem;">
                        <div style="font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: var(--brown-primary); margin-bottom: 0.35rem;">
                            📊 Pipeline Status
                        </div>
                        <form method="POST">
                            <input type="hidden" name="update_status" value="1">
                            <input type="hidden" name="lead_id" value="<?= htmlspecialchars($l['id']) ?>">
                            <select name="status" onchange="this.form.submit()" class="form-select" style="padding: 0.55rem 0.85rem; font-size: 0.8125rem; font-weight: 800; border-radius: 0.65rem; background-color: #ffffff; border: 1px solid var(--border-medium); cursor: pointer;">
                                <option value="New Lead" <?= $l['status'] === 'New Lead' ? 'selected' : '' ?>>🔵 New Lead (Pending Outreach)</option>
                                <option value="In Discussion" <?= $l['status'] === 'In Discussion' ? 'selected' : '' ?>>🟡 In Discussion</option>
                                <option value="Follow-up" <?= $l['status'] === 'Follow-up' ? 'selected' : '' ?>>🟣 Follow-up Required</option>
                                <option value="Converted" <?= $l['status'] === 'Converted' ? 'selected' : '' ?>>🟢 Converted Client</option>
                            </select>
                        </form>
                        <div style="font-size: 0.6875rem; color: var(--text-dim); margin-top: 0.5rem;">
                            Auto-saves immediately on change
                        </div>
                    </div>

                </div>

            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
"""

with open(leads_file, "w", encoding="utf-8") as f:
    f.write(leads_code.strip())

print(f"Updated {leads_file} with luxury spacious CRM cards & zero clutter layout!")
