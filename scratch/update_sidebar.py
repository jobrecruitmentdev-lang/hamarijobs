import os

sidebar_file = r"C:\hk\prmarketing\backend\admin\layout\sidebar.php"

sidebar_content = """<?php
/**
 * PR Marketing Ventures — Left Navigation Sidebar (Warm Cream & Light Brown)
 */
$currentPage = basename($_SERVER['PHP_SELF']);

// Count new client leads for badge
$leadBadgeCount = 0;
try {
    $db = Database::getConnection();
    $leadBadgeCount = (int)$db->query("SELECT COUNT(*) FROM pr_client_leads WHERE status = 'New Lead'")->fetchColumn();
} catch (Exception $e) {
    // Ignore if table not created yet
}
?>
<!-- Left Sidebar Container -->
<aside class="sidebar">
    
    <!-- Top Brand Section -->
    <div>
        <div style="height: 4.5rem; padding: 0 1.5rem; display: flex; align-items: center; gap: 0.875rem; border-bottom: 1px solid var(--border-subtle); background-color: #eee5d8;">
            <div style="width: 2.25rem; height: 2.25rem; border-radius: 0.65rem; background: linear-gradient(135deg, #a0683b 0%, #7d4a22 100%); display: flex; align-items: center; justify-content: center; color: #ffffff; font-weight: 900; font-size: 1.125rem; font-family: 'Space Grotesk', sans-serif; box-shadow: 0 4px 12px rgba(140, 88, 53, 0.3); border: 1px solid rgba(255, 255, 255, 0.3);">
                PR
            </div>
            <div>
                <h1 style="font-size: 0.9375rem; font-weight: 900; color: var(--text-main); font-family: 'Space Grotesk', sans-serif; letter-spacing: -0.02em; line-height: 1.2;">PR Marketing</h1>
                <p style="font-size: 0.625rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--brown-primary); font-weight: 800;">Enterprise CMS</p>
            </div>
        </div>

        <!-- Navigation Menu -->
        <nav style="padding: 1.25rem 0.875rem; display: flex; flex-direction: column; gap: 0.375rem;">
            <p style="padding: 0 0.75rem; font-size: 0.625rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-dim); margin-bottom: 0.375rem;">Core Operations</p>
            
            <!-- 1. Dashboard -->
            <a href="/admin/index.php" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 0.875rem; border-radius: 0.75rem; font-size: 0.8125rem; font-weight: 700; transition: all 0.2s; <?= ($currentPage === 'index.php') ? 'background: #e6dac8; color: var(--brown-primary); border: 1px solid var(--border-medium); font-weight: 800;' : 'color: var(--text-muted); border: 1px solid transparent;' ?>">
                <svg style="color: <?= ($currentPage === 'index.php') ? 'var(--brown-primary)' : 'var(--text-dim)' ?>;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
                <span>Dashboard</span>
            </a>

            <!-- 2. Stories & Articles -->
            <a href="/admin/posts.php" style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 0.875rem; border-radius: 0.75rem; font-size: 0.8125rem; font-weight: 700; transition: all 0.2s; <?= ($currentPage === 'posts.php') ? 'background: #e6dac8; color: var(--brown-primary); border: 1px solid var(--border-medium); font-weight: 800;' : 'color: var(--text-muted); border: 1px solid transparent;' ?>">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <svg style="color: <?= ($currentPage === 'posts.php') ? 'var(--brown-primary)' : 'var(--text-dim)' ?>;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                    </svg>
                    <span>Stories & Articles</span>
                </div>
                <span class="badge-brown">4 Live</span>
            </a>

            <!-- 3. Categories -->
            <a href="/admin/categories.php" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 0.875rem; border-radius: 0.75rem; font-size: 0.8125rem; font-weight: 700; transition: all 0.2s; <?= ($currentPage === 'categories.php') ? 'background: #e6dac8; color: var(--brown-primary); border: 1px solid var(--border-medium); font-weight: 800;' : 'color: var(--text-muted); border: 1px solid transparent;' ?>">
                <svg style="color: <?= ($currentPage === 'categories.php') ? 'var(--brown-primary)' : 'var(--text-dim)' ?>;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
                <span>Categories</span>
            </a>

            <!-- 4. Media Library -->
            <a href="/admin/media.php" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 0.875rem; border-radius: 0.75rem; font-size: 0.8125rem; font-weight: 700; transition: all 0.2s; <?= ($currentPage === 'media.php') ? 'background: #e6dac8; color: var(--brown-primary); border: 1px solid var(--border-medium); font-weight: 800;' : 'color: var(--text-muted); border: 1px solid transparent;' ?>">
                <svg style="color: <?= ($currentPage === 'media.php') ? 'var(--brown-primary)' : 'var(--text-dim)' ?>;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>Media Library</span>
            </a>

            <!-- 5. Client Data / Leads -->
            <a href="/admin/leads.php" style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 0.875rem; border-radius: 0.75rem; font-size: 0.8125rem; font-weight: 700; transition: all 0.2s; <?= ($currentPage === 'leads.php') ? 'background: #e6dac8; color: var(--brown-primary); border: 1px solid var(--border-medium); font-weight: 800;' : 'color: var(--text-muted); border: 1px solid transparent;' ?>">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <svg style="color: <?= ($currentPage === 'leads.php') ? 'var(--brown-primary)' : 'var(--text-dim)' ?>;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <span>Client Data</span>
                </div>
                <?php if ($leadBadgeCount > 0): ?>
                    <span class="badge-emerald" style="font-size: 0.625rem; padding: 0.15rem 0.45rem;">
                        <?= $leadBadgeCount ?> New
                    </span>
                <?php else: ?>
                    <span style="font-size: 0.625rem; color: var(--text-dim); font-weight: 800;">
                        Leads
                    </span>
                <?php endif; ?>
            </a>

            <div style="padding-top: 1rem;">
                <p style="padding: 0 0.75rem; font-size: 0.625rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-dim); margin-bottom: 0.375rem;">Live Frontend</p>
                <a href="/startup-stories/" target="_blank" style="display: flex; align-items: center; justify-content: space-between; padding: 0.65rem 0.875rem; border-radius: 0.75rem; font-size: 0.75rem; font-weight: 700; color: var(--text-main); background: #ffffff; border: 1px solid var(--border-subtle); box-shadow: 0 2px 8px rgba(80, 50, 20, 0.03);">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span style="width: 0.5rem; height: 0.5rem; border-radius: 50%; background: #10b981;"></span>
                        <span>Startup Stories</span>
                    </div>
                    <span style="color: var(--brown-primary); font-weight: 800;">↗</span>
                </a>
            </div>
        </nav>
    </div>

    <!-- Bottom User Profile Card -->
    <div style="padding: 1rem; border-top: 1px solid var(--border-subtle); background-color: #eee5d8;">
        <div style="padding: 0.65rem 0.75rem; border-radius: 0.875rem; background: #ffffff; border: 1px solid var(--border-subtle); display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 0.625rem; overflow: hidden;">
                <div style="width: 2rem; height: 2rem; border-radius: 0.5rem; background: #f3ece1; border: 1px solid var(--border-medium); color: var(--brown-primary); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.75rem; flex-shrink: 0;">
                    <?= strtoupper(substr($currentUser['name'] ?? 'A', 0, 1)) ?>
                </div>
                <div style="overflow: hidden;">
                    <p style="font-size: 0.75rem; font-weight: 800; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($currentUser['name'] ?? 'Admin') ?></p>
                    <p style="font-size: 0.625rem; color: #059669; font-weight: 700;">● <?= htmlspecialchars($currentUser['role'] ?? 'SuperAdmin') ?></p>
                </div>
            </div>
            <a href="/admin/logout.php" title="Sign Out" style="padding: 0.35rem; border-radius: 0.375rem; color: var(--text-dim); transition: color 0.2s;" onmouseover="this.style.color='var(--brown-primary)'" onmouseout="this.style.color='var(--text-dim)'">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
            </a>
        </div>
    </div>
</aside>

<!-- Right Main Content Wrapper -->
<div class="main-wrapper">
    
    <!-- Topbar -->
    <header class="topbar">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <h2 style="font-size: 1.125rem; font-weight: 900; color: var(--text-main); font-family: 'Space Grotesk', sans-serif;">
                <?= $pageTitle ?? 'Control Center' ?>
            </h2>
        </div>

        <div style="display: flex; align-items: center; gap: 0.875rem;">
            <a href="/startup-stories/" target="_blank" class="cream-btn" style="padding: 0.45rem 0.875rem; font-size: 0.75rem;">
                Visit Website ↗
            </a>
        </div>
    </header>

    <!-- Main Content Body -->
    <main class="content-body">
"""

with open(sidebar_file, "w", encoding="utf-8") as f:
    f.write(sidebar_content.strip())

print(f"Updated {sidebar_file} with Client Data menu item!")
