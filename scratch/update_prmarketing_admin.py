import os

file_path = r"C:\hk\prmarketing\backend\admin\index.php"

content = """<?php
/**
 * PR Marketing Ventures — Enterprise Admin Dashboard (Warm Cream & Luxury Espresso)
 */
$pageTitle = "Dashboard Overview";
require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/layout/sidebar.php';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../repositories/CategoryRepository.php';
require_once __DIR__ . '/../repositories/PostRepository.php';

$categoryRepo = new CategoryRepository();
$postRepo = new PostRepository();

$categories = $categoryRepo->getAll();
$posts = $postRepo->getAll(100, 0, null, null);

// Fetch Media Count
$db = Database::getConnection();
$medStmt = $db->query("SELECT COUNT(*) as total FROM pr_media_files");
$mediaCount = $medStmt->fetch()['total'] ?? 0;
?>

<!-- 1. Luxury Hero Banner -->
<div style="position: relative; overflow: hidden; border-radius: 1.25rem; background: linear-gradient(135deg, #f7f1e7 0%, #ebe0cf 100%); padding: 2rem 2.25rem; border: 1px solid var(--border-medium); box-shadow: 0 4px 20px rgba(80, 50, 20, 0.05);">
    <div style="position: absolute; right: -20px; bottom: -30px; font-size: 8rem; opacity: 0.04; font-family: 'Space Grotesk', sans-serif; font-weight: 900; pointer-events: none;">PR</div>
    
    <div style="position: relative; z-index: 10; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1.5rem;">
        <div style="max-width: 42rem; display: flex; flex-direction: column; gap: 0.5rem;">
            <div style="display: inline-flex; align-items: center; gap: 0.45rem; padding: 0.25rem 0.75rem; border-radius: 9999px; background: #ffffff; border: 1px solid var(--border-medium); font-size: 0.7rem; font-weight: 800; color: var(--brown-primary); text-transform: uppercase; letter-spacing: 0.06em; width: fit-content; box-shadow: 0 2px 8px rgba(80, 50, 20, 0.04);">
                <span>✨</span> Enterprise Publishing Suite
            </div>
            <h1 style="font-size: 1.75rem; font-weight: 900; color: var(--text-main); font-family: 'Space Grotesk', sans-serif; letter-spacing: -0.02em; line-height: 1.2;">
                Welcome back, <?= htmlspecialchars($currentUser['name'] ?? 'Admin') ?>
            </h1>
            <p style="font-size: 0.875rem; color: var(--text-muted); line-height: 1.6;">
                Real-time publishing control center. Manage high-authority startup stories, multi-descriptions, structured FAQs, and 4K media assets with instant database synchronization.
            </p>
        </div>

        <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem;">
            <a href="/admin/posts.php?action=create" class="gold-btn" style="padding: 0.75rem 1.35rem; font-size: 0.8125rem;">
                <span>+</span> New Story
            </a>
            <a href="/startup-stories/" target="_blank" class="cream-btn" style="padding: 0.75rem 1.25rem; font-size: 0.8125rem; background: #ffffff;">
                <span>🌐</span> Live Stories ↗
            </a>
        </div>
    </div>
</div>

<!-- 2. High-Impact KPI Metric Cards -->
<div class="grid-4" style="margin-top: 0.25rem;">
    
    <!-- 1. Live Stories -->
    <div class="glass-card" style="position: relative; overflow: hidden;">
        <div style="position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, #a0683b, #dcc8b4);"></div>
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <p style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; color: var(--brown-primary);">Live Stories</p>
            <div style="width: 2.25rem; height: 2.25rem; border-radius: 0.65rem; background: #f5ece1; border: 1px solid var(--border-medium); display: flex; align-items: center; justify-content: center; font-size: 1rem;">
                📰
            </div>
        </div>
        <p style="font-size: 2.25rem; font-weight: 900; color: var(--text-main); font-family: 'Space Grotesk', sans-serif; margin-top: 0.5rem; line-height: 1;"><?= count($posts) ?></p>
        <div style="display: flex; align-items: center; gap: 0.375rem; margin-top: 0.65rem; font-size: 0.75rem;">
            <span style="color: #059669; font-weight: 800;">● Active</span>
            <span style="color: var(--text-dim);">in database</span>
        </div>
    </div>

    <!-- 2. Categories -->
    <div class="glass-card" style="position: relative; overflow: hidden;">
        <div style="position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, #3b82f6, #93c5fd);"></div>
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <p style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; color: var(--brown-primary);">Taxonomy Categories</p>
            <div style="width: 2.25rem; height: 2.25rem; border-radius: 0.65rem; background: #f5ece1; border: 1px solid var(--border-medium); display: flex; align-items: center; justify-content: center; font-size: 1rem;">
                📁
            </div>
        </div>
        <p style="font-size: 2.25rem; font-weight: 900; color: var(--text-main); font-family: 'Space Grotesk', sans-serif; margin-top: 0.5rem; line-height: 1;"><?= count($categories) ?></p>
        <div style="display: flex; align-items: center; gap: 0.375rem; margin-top: 0.65rem; font-size: 0.75rem;">
            <span style="color: #059669; font-weight: 800;">100% Dynamic</span>
            <span style="color: var(--text-dim);">PR taxonomy</span>
        </div>
    </div>

    <!-- 3. Media Assets -->
    <div class="glass-card" style="position: relative; overflow: hidden;">
        <div style="position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, #8b5cf6, #c4b5fd);"></div>
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <p style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; color: var(--brown-primary);">Media Assets</p>
            <div style="width: 2.25rem; height: 2.25rem; border-radius: 0.65rem; background: #f5ece1; border: 1px solid var(--border-medium); display: flex; align-items: center; justify-content: center; font-size: 1rem;">
                🖼️
            </div>
        </div>
        <p style="font-size: 2.25rem; font-weight: 900; color: var(--text-main); font-family: 'Space Grotesk', sans-serif; margin-top: 0.5rem; line-height: 1;"><?= $mediaCount ?></p>
        <div style="display: flex; align-items: center; gap: 0.375rem; margin-top: 0.65rem; font-size: 0.75rem;">
            <span style="color: var(--brown-primary); font-weight: 800;">4K Visual Suite</span>
            <span style="color: var(--text-dim);">registered</span>
        </div>
    </div>

    <!-- 4. Database Engine -->
    <div class="glass-card" style="position: relative; overflow: hidden;">
        <div style="position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, #10b981, #6ee7b7);"></div>
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <p style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; color: var(--brown-primary);">Database Engine</p>
            <div style="width: 2.25rem; height: 2.25rem; border-radius: 0.65rem; background: #eafaf1; border: 1px solid #a7f3d0; display: flex; align-items: center; justify-content: center; font-size: 1rem;">
                ⚡
            </div>
        </div>
        <p style="font-size: 1.75rem; font-weight: 900; color: #065f46; font-family: 'Space Grotesk', sans-serif; margin-top: 0.5rem; line-height: 1.2;">MySQL 11.8</p>
        <div style="display: flex; align-items: center; gap: 0.375rem; margin-top: 0.65rem; font-size: 0.75rem;">
            <span style="color: #059669; font-weight: 800;">● Online</span>
            <span style="color: var(--text-dim);">UTF8MB4 Unicode</span>
        </div>
    </div>

</div>

<!-- 3. Main 2-Column Content Grid -->
<div class="grid-3-2">
    
    <!-- Left Column: Modern Published Stories Cards List -->
    <div class="glass-card" style="display: flex; flex-direction: column; gap: 1.5rem; padding: 1.75rem;">
        <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid var(--border-subtle);">
            <div>
                <h2 style="font-size: 1.15rem; font-weight: 900; color: var(--text-main); font-family: 'Space Grotesk', sans-serif; letter-spacing: -0.01em;">
                    Published Stories & Blueprints
                </h2>
                <p style="font-size: 0.8125rem; color: var(--text-muted); margin-top: 0.2rem;">
                    Full editorial entities with multi-descriptions, structured FAQs and JSON-LD schema
                </p>
            </div>
            <a href="/admin/posts.php" class="cream-btn" style="font-size: 0.75rem; padding: 0.45rem 0.85rem; font-weight: 800;">
                View All Stories (<?= count($posts) ?>) →
            </a>
        </div>

        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <?php if (empty($posts)): ?>
                <div style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                    <p style="font-size: 1.5rem; margin-bottom: 0.5rem;">📝</p>
                    <p style="font-weight: 700;">No stories published yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($posts as $p): ?>
                    <div style="background: #ffffff; border: 1px solid var(--border-subtle); border-radius: 1rem; padding: 1.25rem 1.5rem; transition: all 0.2s; display: flex; flex-direction: column; gap: 0.75rem; box-shadow: 0 2px 8px rgba(80, 50, 20, 0.02);" onmouseover="this.style.borderColor='var(--border-medium)'; this.style.boxShadow='0 4px 15px rgba(80, 50, 20, 0.06)';" onmouseout="this.style.borderColor='var(--border-subtle)'; this.style.boxShadow='0 2px 8px rgba(80, 50, 20, 0.02)';">
                        
                        <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem;">
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <span class="badge-brown" style="font-size: 0.7rem; padding: 0.2rem 0.6rem;">
                                    <?= htmlspecialchars($p['category_name'] ?? 'PR Marketing') ?>
                                </span>
                                <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;">
                                    ⏱ <?= htmlspecialchars($p['reading_time'] ?? '9 min read') ?>
                                </span>
                            </div>

                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <span style="display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.7rem; font-weight: 800; color: #059669; background: #eafaf1; border: 1px solid #a7f3d0; padding: 0.2rem 0.55rem; border-radius: 9999px;">
                                    ● Published
                                </span>
                            </div>
                        </div>

                        <div>
                            <h3 style="font-size: 1.05rem; font-weight: 800; color: var(--text-main); font-family: 'Space Grotesk', sans-serif; line-height: 1.35; margin-bottom: 0.35rem;">
                                <a href="/startup-stories/<?= htmlspecialchars($p['slug']) ?>/" target="_blank" style="color: inherit;" onmouseover="this.style.color='var(--brown-primary)'" onmouseout="this.style.color='inherit'">
                                    <?= htmlspecialchars($p['title']) ?>
                                </a>
                            </h3>
                            <p style="font-size: 0.8125rem; color: var(--text-muted); line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                <?= htmlspecialchars($p['summary'] ?? '') ?>
                            </p>
                        </div>

                        <div style="display: flex; align-items: center; justify-content: space-between; padding-top: 0.75rem; border-top: 1px solid var(--border-subtle); margin-top: 0.25rem;">
                            <span style="font-size: 0.75rem; color: var(--text-dim); font-family: monospace;">
                                /startup-stories/<?= htmlspecialchars(substr($p['slug'], 0, 30)) ?>...
                            </span>

                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <a href="/startup-stories/<?= htmlspecialchars($p['slug']) ?>/" target="_blank" class="cream-btn" style="padding: 0.35rem 0.75rem; font-size: 0.75rem; font-weight: 800;">
                                    Read ↗
                                </a>
                                <a href="/admin/posts.php?action=edit&id=<?= htmlspecialchars($p['id']) ?>" class="gold-btn" style="padding: 0.35rem 0.75rem; font-size: 0.75rem;">
                                    Edit ✏
                                </a>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right Column: Category Distribution & Automation -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        
        <!-- Category Breakdown Card -->
        <div class="glass-card" style="display: flex; flex-direction: column; gap: 1.25rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 0.75rem; border-bottom: 1px solid var(--border-subtle);">
                <h3 style="font-size: 1rem; font-weight: 900; color: var(--text-main); font-family: 'Space Grotesk', sans-serif;">
                    Category Hierarchy
                </h3>
                <a href="/admin/categories.php" style="font-size: 0.75rem; font-weight: 800; color: var(--brown-primary);">
                    Manage ⚙
                </a>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <?php foreach ($categories as $cat): ?>
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 1rem; border-radius: 0.75rem; background: #faf7f2; border: 1px solid var(--border-subtle); transition: all 0.2s;" onmouseover="this.style.borderColor='var(--border-medium)'" onmouseout="this.style.borderColor='var(--border-subtle)'">
                        <div>
                            <p style="font-size: 0.8125rem; font-weight: 800; color: var(--text-main); font-family: 'Space Grotesk', sans-serif;"><?= htmlspecialchars($cat['name']) ?></p>
                            <p style="font-size: 0.6875rem; color: var(--text-dim); margin-top: 0.1rem; font-family: monospace;"><?= htmlspecialchars($cat['slug']) ?></p>
                        </div>
                        <span class="badge-brown" style="font-size: 0.75rem; padding: 0.25rem 0.65rem; font-weight: 800;">
                            <?= $cat['post_count'] ?? 0 ?> stories
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Automation Publishing Engine Card -->
        <div class="glass-card" style="background: linear-gradient(135deg, #f7f1e7 0%, #eee4d5 100%); display: flex; flex-direction: column; gap: 0.75rem; border: 1px solid var(--border-medium);">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <span style="width: 8px; height: 8px; border-radius: 50%; background: #059669; box-shadow: 0 0 8px rgba(5, 150, 105, 0.5);"></span>
                <h4 style="font-size: 0.75rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.08em; color: var(--brown-primary);">
                    Autonomous Dual-Publisher
                </h4>
            </div>
            <p style="font-size: 0.8125rem; color: var(--text-muted); line-height: 1.6;">
                When batch publishing scripts or Python automation pipelines run, stories and multi-section FAQs are automatically ingested directly into MySQL with zero manual entry.
            </p>
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.25rem; font-size: 0.75rem; font-weight: 700; color: var(--brown-dark);">
                <span>⚡ Auto-Indexed via IndexNow & Google GSC</span>
            </div>
        </div>

    </div>

</div>

<?php
require_once __DIR__ . '/layout/footer.php';
"""

with open(file_path, "w", encoding="utf-8") as f:
    f.write(content.strip())

print(f"Updated {file_path} successfully!")
