import os

header_file = r"C:\hk\prmarketing\backend\admin\layout\header.php"
leads_file = r"C:\hk\prmarketing\backend\admin\leads.php"

# 1. Update header.php CSS
header_code = """<?php
/**
 * PR Marketing Ventures — Admin Header (Warm Cream & Light Brown Luxury Palette)
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../auth_middleware.php';
$currentUser = requireAdminAuth();

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Control Center' ?> | PR Marketing Ventures Admin</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@600;700;800&display=swap" rel="stylesheet">
    
    <style>
        /* ==============================================================================
           WARM CREAM & LIGHT BROWN LUXURY PALETTE (ZERO OVERLAP & OPTIMAL CONTRAST)
           ============================================================================== */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        :root {
            --bg-main: #faf7f2;
            --bg-sidebar: #f3ece1;
            --bg-sidebar-active: #e6dac8;
            --bg-card: #ffffff;
            --bg-subtle: #f8f4ee;
            --border-subtle: #e8dfd3;
            --border-medium: #d6c7b5;
            --border-accent: #b5987e;
            
            --brown-primary: #8c5835;
            --brown-hover: #754423;
            --brown-dark: #4a2d18;
            --brown-light: #dcc8b4;
            
            --text-main: #241810;
            --text-muted: #6e5b4f;
            --text-dim: #9c8778;
            
            --emerald-bg: #eafaf1;
            --emerald-text: #065f46;
            --emerald-border: #a7f3d0;
            
            --red-bg: #fef2f2;
            --red-text: #991b1b;
            --red-border: #fecaca;
        }

        html, body {
            min-height: 100vh;
            width: 100%;
            background-color: var(--bg-main);
            color: var(--text-main);
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            overflow-x: hidden;
            display: flex;
            -webkit-font-smoothing: antialiased;
        }

        a { color: inherit; text-decoration: none; }

        /* Strict SVG dimensions */
        svg {
            display: inline-block !important;
            width: 1.15rem !important;
            height: 1.15rem !important;
            max-width: 1.15rem !important;
            max-height: 1.15rem !important;
            flex-shrink: 0 !important;
            vertical-align: middle;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 7px; height: 7px; }
        ::-webkit-scrollbar-track { background: #faf7f2; }
        ::-webkit-scrollbar-thumb { background: #d6c7b5; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #b5987e; }

        .font-heading { font-family: 'Space Grotesk', sans-serif; }

        /* Layout Structure */
        .sidebar {
            width: 16.5rem;
            min-width: 16.5rem;
            background-color: var(--bg-sidebar);
            border-right: 1px solid var(--border-subtle);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            flex-shrink: 0;
            height: 100vh;
            position: sticky;
            top: 0;
            overflow-y: auto;
            z-index: 30;
        }

        .main-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            width: calc(100% - 16.5rem);
            max-width: calc(100% - 16.5rem);
            overflow-x: hidden;
            background-color: var(--bg-main);
        }

        .topbar {
            height: 4.5rem;
            border-bottom: 1px solid var(--border-subtle);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            padding: 0 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
            position: sticky;
            top: 0;
            z-index: 20;
        }

        .content-body {
            flex: 1;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            gap: 1.75rem;
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
        }

        /* Cream & Light Brown Cards */
        .glass-card {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: 1.25rem;
            padding: 1.75rem;
            box-shadow: 0 4px 20px rgba(80, 50, 20, 0.04);
            transition: all 0.2s ease;
        }
        .glass-card:hover {
            border-color: var(--border-medium);
            box-shadow: 0 6px 25px rgba(80, 50, 20, 0.07);
        }

        /* Buttons */
        .gold-btn, .brown-btn {
            background: linear-gradient(135deg, #a0683b 0%, #7d4a22 100%);
            color: #ffffff;
            font-weight: 800;
            border-radius: 0.75rem;
            padding: 0.65rem 1.25rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
            font-size: 0.8125rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(140, 88, 53, 0.25);
            transition: all 0.2s;
        }
        .gold-btn:hover, .brown-btn:hover {
            background: linear-gradient(135deg, #8f582e 0%, #683a15 100%);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(140, 88, 53, 0.35);
        }

        .dark-btn, .cream-btn {
            background: #ffffff;
            color: var(--text-main);
            border: 1px solid var(--border-medium);
            font-weight: 700;
            border-radius: 0.75rem;
            padding: 0.6rem 1.15rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            font-size: 0.8125rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .dark-btn:hover, .cream-btn:hover {
            background: #f5ede2;
            color: var(--brown-primary);
            border-color: var(--brown-primary);
        }

        /* Form Inputs */
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 0.85rem 1.125rem;
            background-color: #ffffff;
            border: 1px solid var(--border-medium);
            border-radius: 0.75rem;
            color: var(--text-main);
            font-size: 0.875rem;
            font-family: inherit;
            outline: none;
            transition: all 0.2s;
        }
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            border-color: var(--brown-primary);
            box-shadow: 0 0 0 3px rgba(140, 88, 53, 0.12);
        }

        /* Fluid Table (Zero Horizontal Scroll) */
        .admin-table-container {
            background: #ffffff;
            border: 1px solid var(--border-subtle);
            border-radius: 1rem;
            overflow: hidden;
            width: 100%;
            box-shadow: 0 4px 20px rgba(80, 50, 20, 0.03);
        }
        .admin-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            text-align: left;
            font-size: 0.8125rem;
        }
        .admin-table th {
            background-color: #f7f1e7;
            padding: 0.875rem 1rem;
            font-weight: 800;
            color: var(--brown-dark);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-size: 0.7rem;
            border-bottom: 1px solid var(--border-medium);
        }
        .admin-table td {
            padding: 1rem 1rem;
            border-bottom: 1px solid var(--border-subtle);
            color: var(--text-main);
            vertical-align: middle;
        }
        .admin-table tbody tr {
            transition: background-color 0.15s ease;
        }
        .admin-table tbody tr:hover {
            background-color: #faf6f0;
        }
        .admin-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Status Badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.3rem 0.65rem;
            border-radius: 9999px;
            font-size: 0.6875rem;
            font-weight: 800;
            letter-spacing: 0.02em;
            white-space: nowrap;
        }
        .status-new {
            background: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }
        .status-discussion {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        .status-followup {
            background: #fae8ff;
            color: #86198f;
            border: 1px solid #f5d0fe;
        }
        .status-converted {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }

        /* Grids */
        .grid-4 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.25rem;
        }
        .grid-3-2 {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.75rem;
        }
        @media (max-width: 1024px) {
            .sidebar { display: none; }
            .main-wrapper { width: 100%; max-width: 100%; }
            .grid-3-2 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
"""

with open(header_file, "w", encoding="utf-8") as f:
    f.write(header_code.strip())

# 2. Update leads.php with fluid responsive layout and zero horizontal scroll
leads_code = """<?php
/**
 * PR Marketing Ventures — Client Data & Tools Inquiries Intelligence Panel
 * Fluid Responsive Layout (Zero Horizontal Scroll, Full Vertical Scroll)
 */
$pageTitle = "Client Data & Leads";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../auth_middleware.php';
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
        $msg = "Lead status successfully updated to {$newStatus}.";
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
        <div style="display: inline-flex; align-items: center; gap: 0.45rem; padding: 0.25rem 0.75rem; border-radius: 9999px; background: #ffffff; border: 1px solid var(--border-medium); font-size: 0.7rem; font-weight: 800; color: var(--brown-primary); text-transform: uppercase; letter-spacing: 0.06em; width: fit-content;">
            <span>👥</span> Real-Time Client Lead Engine
        </div>
        <h1 style="font-size: 1.75rem; font-weight: 900; color: var(--text-main); font-family: 'Space Grotesk', sans-serif; letter-spacing: -0.02em;">
            Client Data & Inquiries
        </h1>
        <p style="font-size: 0.8125rem; color: var(--text-muted);">
            Real-time inquiries captured from Domain Authority Checker, Google Review QR, and WhatsApp Link Generator.
        </p>
    </div>

    <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
        <a href="/admin/leads.php?action=export_csv" class="gold-btn" style="padding: 0.7rem 1.25rem;">
            <span>📥</span> Export to Excel / CSV
        </a>
        <a href="/tools/" target="_blank" class="cream-btn" style="padding: 0.7rem 1.15rem; background: #ffffff;">
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

<!-- 2. KPI Summary Cards -->
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
<div class="glass-card" style="padding: 1.25rem;">
    <form method="GET" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 0.75rem; align-items: center;">
        <input type="text" name="search" value="<?= htmlspecialchars($searchQuery ?? '') ?>" placeholder="🔍 Search by Client Name, Phone, Brand, Domain URL..." class="form-input" style="padding: 0.65rem 1rem;">
        
        <select name="status" class="form-select" style="padding: 0.65rem 1rem;">
            <option value="">All Lead Statuses</option>
            <option value="New Lead" <?= $statusFilter === 'New Lead' ? 'selected' : '' ?>>New Lead</option>
            <option value="In Discussion" <?= $statusFilter === 'In Discussion' ? 'selected' : '' ?>>In Discussion</option>
            <option value="Follow-up" <?= $statusFilter === 'Follow-up' ? 'selected' : '' ?>>Follow-up</option>
            <option value="Converted" <?= $statusFilter === 'Converted' ? 'selected' : '' ?>>Converted</option>
        </select>

        <select name="tool" class="form-select" style="padding: 0.65rem 1rem;">
            <option value="">All Source Tools</option>
            <option value="Domain Authority Checker" <?= $toolFilter === 'Domain Authority Checker' ? 'selected' : '' ?>>Domain Authority</option>
            <option value="Google Review QR Generator" <?= $toolFilter === 'Google Review QR Generator' ? 'selected' : '' ?>>Google Review QR</option>
            <option value="WhatsApp Link Generator" <?= $toolFilter === 'WhatsApp Link Generator' ? 'selected' : '' ?>>WhatsApp Link Gen</option>
        </select>

        <div style="display: flex; gap: 0.5rem;">
            <button type="submit" class="gold-btn" style="padding: 0.65rem 1.15rem; font-size: 0.75rem;">Filter</button>
            <?php if ($searchQuery || $statusFilter || $toolFilter): ?>
                <a href="/admin/leads.php" class="cream-btn" style="padding: 0.65rem 1rem; font-size: 0.75rem;">Clear</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- 4. Fluid Client Data Table (Zero Horizontal Scroll) -->
<div class="admin-table-container">
    <?php if (empty($leads)): ?>
        <div style="padding: 4rem 2rem; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 1rem;">
            <div style="width: 3.5rem; height: 3.5rem; border-radius: 1rem; background: #f5ede2; border: 1px solid var(--border-medium); display: flex; align-items: center; justify-content: center; font-size: 1.75rem;">
                👥
            </div>
            <div>
                <h3 style="font-size: 1.125rem; font-weight: 800; color: var(--text-main); font-family: 'Space Grotesk', sans-serif;">
                    No Client Inquiries In Database Yet
                </h3>
                <p style="font-size: 0.8125rem; color: var(--text-muted); max-width: 32rem; margin: 0.35rem auto 0; line-height: 1.5;">
                    Real-time inquiries submitted by visitors on Domain Authority Checker, Google Review QR, or WhatsApp Link Generator will automatically appear here.
                </p>
            </div>
            <div style="display: inline-flex; align-items: center; gap: 0.45rem; padding: 0.35rem 0.85rem; border-radius: 9999px; background: #eafaf1; border: 1px solid #a7f3d0; color: #065f46; font-size: 0.75rem; font-weight: 800;">
                <span>●</span> Database Listening Active
            </div>
        </div>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 20%;">Client & Brand</th>
                    <th style="width: 18%;">Contact / WhatsApp</th>
                    <th style="width: 16%;">Domain & Stage</th>
                    <th style="width: 16%;">Source Tool</th>
                    <th style="width: 16%;">Status Tracker</th>
                    <th style="width: 14%; text-align: right;">Date & Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($leads as $l): ?>
                    <?php 
                        $statusClass = 'status-new';
                        if ($l['status'] === 'In Discussion') $statusClass = 'status-discussion';
                        elseif ($l['status'] === 'Follow-up') $statusClass = 'status-followup';
                        elseif ($l['status'] === 'Converted') $statusClass = 'status-converted';

                        $cleanPhone = preg_replace('/[^0-9]/', '', $l['phone_number']);
                        if (strlen($cleanPhone) === 10) $cleanPhone = '91' . $cleanPhone;
                        
                        $encodedMsg = urlencode("Hello " . $l['full_name'] . ", this is PR Marketing Ventures regarding your inquiry on " . $l['tool_used'] . ". How can we assist your business growth today?");
                        $waUrl = "https://wa.me/{$cleanPhone}?text={$encodedMsg}";
                    ?>
                    <tr>
                        <!-- 1. Client & Brand -->
                        <td>
                            <div style="font-weight: 800; color: var(--text-main); font-size: 0.875rem;">
                                <?= htmlspecialchars($l['full_name']) ?>
                            </div>
                            <?php if (!empty($l['business_name'])): ?>
                                <div style="font-size: 0.75rem; color: var(--brown-primary); font-weight: 700; margin-top: 0.15rem; display: flex; align-items: center; gap: 0.25rem;">
                                    <span>🏢</span> <?= htmlspecialchars($l['business_name']) ?>
                                </div>
                            <?php else: ?>
                                <div style="font-size: 0.6875rem; color: var(--text-dim);">Individual Inquiry</div>
                            <?php endif; ?>
                        </td>

                        <!-- 2. Contact & 1-Click WhatsApp -->
                        <td>
                            <div style="font-family: 'Space Grotesk', sans-serif; font-weight: 700; color: var(--text-main); font-size: 0.8125rem;">
                                <?= htmlspecialchars($l['phone_number']) ?>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.4rem; margin-top: 0.35rem;">
                                <a href="<?= $waUrl ?>" target="_blank" style="display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.5rem; border-radius: 0.45rem; background: #25d366; color: #ffffff; font-size: 0.6875rem; font-weight: 800; text-decoration: none;">
                                    💬 WhatsApp
                                </a>
                                <a href="tel:<?= htmlspecialchars($l['phone_number']) ?>" style="display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.5rem; border-radius: 0.45rem; background: #f3ece1; color: var(--text-main); font-size: 0.6875rem; font-weight: 700; text-decoration: none; border: 1px solid var(--border-medium);">
                                    📞 Call
                                </a>
                            </div>
                        </td>

                        <!-- 3. Domain & Business Stage -->
                        <td>
                            <?php if (!empty($l['website_url'])): ?>
                                <?php 
                                    $url = $l['website_url'];
                                    if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                                        $url = 'https://' . $url;
                                    }
                                ?>
                                <div>
                                    <a href="<?= htmlspecialchars($url) ?>" target="_blank" style="font-weight: 700; color: #2563eb; text-decoration: underline; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 0.2rem; word-break: break-all;">
                                        <?= htmlspecialchars(parse_url($url, PHP_URL_HOST) ?: $l['website_url']) ?> ↗
                                    </a>
                                </div>
                            <?php endif; ?>
                            <div style="font-size: 0.6875rem; color: var(--text-muted); margin-top: 0.15rem;">
                                <?= htmlspecialchars($l['business_stage'] ?: 'Not Specified') ?>
                            </div>
                        </td>

                        <!-- 4. Source Tool -->
                        <td>
                            <span style="display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.25rem 0.55rem; border-radius: 0.5rem; background: #fbf5ee; border: 1px solid var(--border-medium); font-size: 0.6875rem; font-weight: 700; color: var(--brown-dark);">
                                🛠️ <?= htmlspecialchars($l['tool_used']) ?>
                            </span>
                        </td>

                        <!-- 5. Status Tracker -->
                        <td>
                            <form method="POST" style="display: inline-block;">
                                <input type="hidden" name="update_status" value="1">
                                <input type="hidden" name="lead_id" value="<?= htmlspecialchars($l['id']) ?>">
                                <select name="status" onchange="this.form.submit()" class="form-select" style="padding: 0.35rem 0.65rem; font-size: 0.75rem; font-weight: 700; border-radius: 0.5rem; width: auto; background-color: #faf7f2;">
                                    <option value="New Lead" <?= $l['status'] === 'New Lead' ? 'selected' : '' ?>>● New Lead</option>
                                    <option value="In Discussion" <?= $l['status'] === 'In Discussion' ? 'selected' : '' ?>>💬 In Discussion</option>
                                    <option value="Follow-up" <?= $l['status'] === 'Follow-up' ? 'selected' : '' ?>>⏱ Follow-up</option>
                                    <option value="Converted" <?= $l['status'] === 'Converted' ? 'selected' : '' ?>>✓ Converted</option>
                                </select>
                            </form>
                        </td>

                        <!-- 6. Date & Actions -->
                        <td style="text-align: right;">
                            <div style="font-size: 0.6875rem; color: var(--text-muted);">
                                <?= date('d M Y, h:i A', strtotime($l['created_at'])) ?>
                            </div>
                            <div style="margin-top: 0.35rem;">
                                <a href="/admin/leads.php?action=delete&id=<?= urlencode($l['id']) ?>" onclick="return confirm('Are you sure you want to delete this lead record?')" style="font-size: 0.6875rem; font-weight: 700; color: #dc2626; text-decoration: none;">
                                    Delete ✕
                                </a>
                            </div>
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

print(f"Updated {header_file} and {leads_file} with fluid layout & zero horizontal scroll!")
