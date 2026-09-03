import os

posts_file = r"C:\hk\prmarketing\backend\admin\posts.php"

posts_php_content = """<?php
/**
 * PR Marketing Ventures — Story / Post Management Panel (Warm Cream & Light Brown)
 */
$pageTitle = "Stories & Articles";
require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/layout/sidebar.php';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../repositories/CategoryRepository.php';
require_once __DIR__ . '/../repositories/PostRepository.php';

$db = Database::getConnection();
$categoryRepo = new CategoryRepository();
$postRepo = new PostRepository();
$msg = '';
$error = '';

$action = $_GET['action'] ?? 'list';
$editId = $_GET['id'] ?? null;

// Handle Story Save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_post'])) {
    $id = !empty($_POST['id']) ? $_POST['id'] : null;
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '') ?: strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    $summary = trim($_POST['summary'] ?? '');
    $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $readTime = trim($_POST['reading_time'] ?? '9 min read');
    $status = $_POST['status'] ?? 'Published';
    $coverImage = trim($_POST['cover_image'] ?? '');

    // Handle Direct Image File Upload
    if (!empty($_FILES['cover_image_file']) && $_FILES['cover_image_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['cover_image_file']['tmp_name'];
        $origName = $_FILES['cover_image_file']['name'];
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        if (in_array($ext, $allowedExts)) {
            $filename = ($slug ?: 'story-cover') . '-' . time() . '.' . $ext;
            
            // Directories on local and server
            $dirs = [
                __DIR__ . '/../../images/guides/',
                __DIR__ . '/../../website/public/images/guides/',
                __DIR__ . '/../public/images/guides/'
            ];

            $saved = false;
            foreach ($dirs as $dir) {
                if (!is_dir($dir)) {
                    @mkdir($dir, 0755, true);
                }
                if (@copy($fileTmp, $dir . $filename)) {
                    $saved = true;
                }
            }

            if ($saved || @move_uploaded_file($fileTmp, $dirs[0] . $filename)) {
                $coverImage = '/images/guides/' . $filename;
            }
        } else {
            $error = "Invalid image type. Allowed: JPG, JPEG, PNG, WEBP";
        }
    }

    // Parse Sections
    $sections = [];
    if (!empty($_POST['section_headings']) && is_array($_POST['section_headings'])) {
        foreach ($_POST['section_headings'] as $idx => $heading) {
            $h = trim($heading);
            $c = trim($_POST['section_contents'][$idx] ?? '');
            if ($h || $c) {
                $sections[] = [
                    'heading' => $h ?: "Section " . ($idx + 1),
                    'content' => $c,
                    'sort_order' => $idx + 1
                ];
            }
        }
    }

    // Parse FAQs
    $faqs = [];
    if (!empty($_POST['faq_questions']) && is_array($_POST['faq_questions'])) {
        foreach ($_POST['faq_questions'] as $idx => $q) {
            $question = trim($q);
            $answer = trim($_POST['faq_answers'][$idx] ?? '');
            if ($question && $answer) {
                $faqs[] = [
                    'question' => $question,
                    'answer' => $answer,
                    'sort_order' => $idx + 1
                ];
            }
        }
    }

    $mediaFiles = [];
    if ($coverImage) {
        $mediaFiles[] = ['file_path' => $coverImage, 'context' => 'featured', 'alt_text' => $title];
    }

    if ($title && empty($error)) {
        try {
            $postData = [
                'id' => $id,
                'title' => $title,
                'slug' => $slug,
                'summary' => $summary,
                'category_id' => $categoryId,
                'reading_time' => $readTime,
                'status' => $status,
                'sections' => $sections,
                'faqs' => $faqs,
                'media_files' => $mediaFiles,
                'seo' => [
                    'meta_title' => $title . ' | PR Marketing Ventures',
                    'meta_description' => $summary,
                    'canonical_url' => "/startup-stories/{$slug}/",
                    'og_title' => $title,
                    'og_description' => $summary,
                    'og_image' => $coverImage
                ]
            ];

            $postRepo->create($postData);
            $msg = "Story '{$title}' saved and published successfully to database!";
            $action = 'list';
        } catch (Exception $e) {
            $error = "Error saving story: " . $e->getMessage();
        }
    }
}

// Handle Delete
if ($action === 'delete' && $editId) {
    try {
        $postRepo->delete($editId);
        $msg = "Story moved to trash successfully!";
        $action = 'list';
    } catch (Exception $e) {
        $error = "Error deleting story: " . $e->getMessage();
    }
}

$categories = $categoryRepo->getAll();
$posts = $postRepo->getAll(100, 0, null, null);
$editPost = ($action === 'edit' && $editId) ? $postRepo->getById($editId) : null;

// Fetch all available media files for Media Library Picker
$allMediaStmt = $db->query("SELECT * FROM pr_media_files ORDER BY created_at DESC");
$availableMedia = $allMediaStmt->fetchAll();
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

<?php if ($action === 'create' || $action === 'edit'): ?>
    <!-- Full-Featured Multi-Section Story Editor with Image Upload -->
    <div style="max-width: 56rem; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem; width: 100%;">
        
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <a href="/admin/posts.php" style="font-size: 0.75rem; font-weight: 800; color: var(--brown-primary); display: flex; align-items: center; gap: 0.25rem; margin-bottom: 0.25rem;">
                    ← Back to Stories
                </a>
                <h2 style="font-size: 1.25rem; font-weight: 900; color: var(--text-main); font-family: 'Space Grotesk', sans-serif;">
                    <?= $editPost ? 'Edit Story' : 'Create Multi-Section Story' ?>
                </h2>
            </div>
            <span class="badge-brown">
                4K Media + Multi-Section Suite
            </span>
        </div>

        <form method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 1.75rem;">
            <input type="hidden" name="save_post" value="1">
            <?php if ($editPost): ?>
                <input type="hidden" name="id" value="<?= htmlspecialchars($editPost['id']) ?>">
            <?php endif; ?>

            <!-- 1. Core Meta Card -->
            <div class="glass-card" style="display: flex; flex-direction: column; gap: 1rem;">
                <h3 style="font-size: 0.8125rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.06em; color: var(--brown-primary); font-family: 'Space Grotesk', sans-serif;">1. Core Information</h3>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem;">
                    <div style="grid-column: 1 / -1;">
                        <label style="display: block; font-size: 0.6875rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-main); margin-bottom: 0.375rem;">Article Title</label>
                        <input type="text" name="title" required value="<?= htmlspecialchars($editPost['title'] ?? '') ?>" placeholder="e.g. AI Agent Workflows and Startup Valuation Dynamics 2026" class="form-input">
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.6875rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-main); margin-bottom: 0.375rem;">URL Slug</label>
                        <input type="text" name="slug" value="<?= htmlspecialchars($editPost['slug'] ?? '') ?>" placeholder="e.g. ai-agent-workflows-startup-valuation-dynamics-2026" class="form-input">
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.6875rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-main); margin-bottom: 0.375rem;">Category</label>
                        <select name="category_id" class="form-select">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= ($editPost && $editPost['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="grid-column: 1 / -1;">
                        <label style="display: block; font-size: 0.6875rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-main); margin-bottom: 0.375rem;">Executive Summary / Key Takeaways</label>
                        <textarea name="summary" rows="3" placeholder="Executive summary..." class="form-textarea"><?= htmlspecialchars($editPost['summary'] ?? '') ?></textarea>
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.6875rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-main); margin-bottom: 0.375rem;">Reading Time</label>
                        <input type="text" name="reading_time" value="<?= htmlspecialchars($editPost['reading_time'] ?? '9 min read') ?>" class="form-input">
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.6875rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-main); margin-bottom: 0.375rem;">Publishing Status</label>
                        <select name="status" class="form-select">
                            <option value="Published" <?= ($editPost && $editPost['status'] === 'Published') ? 'selected' : '' ?>>Published (Live on Website)</option>
                            <option value="Draft" <?= ($editPost && $editPost['status'] === 'Draft') ? 'selected' : '' ?>>Draft (Internal)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- 2. Featured Cover Image (Direct Upload + Media Library Picker) -->
            <div class="glass-card" style="display: flex; flex-direction: column; gap: 1rem;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <h3 style="font-size: 0.8125rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.06em; color: var(--brown-primary); font-family: 'Space Grotesk', sans-serif;">2. Featured Cover Image</h3>
                        <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">Upload a high-resolution 16:9 visual from your device or select from Media Library</p>
                    </div>
                    <button type="button" onclick="openMediaModal()" class="cream-btn">
                        🖼️ Choose from Media Library
                    </button>
                </div>

                <?php 
                $currentCover = $editPost['media']['featured'] ?? $editPost['cover_image'] ?? '';
                ?>

                <div style="display: grid; grid-template-columns: 240px 1fr; gap: 1.25rem; align-items: center;">
                    <!-- Image Preview Box -->
                    <div style="aspect-ratio: 16/9; width: 100%; border-radius: 0.75rem; border: 2px dashed var(--border-subtle); background: var(--bg-subtle); overflow: hidden; display: flex; align-items: center; justify-content: center; position: relative;" id="preview_container">
                        <img id="cover_img_preview" src="<?= htmlspecialchars($currentCover ?: '/images/guides/cover-placeholder.jpg') ?>" alt="Cover Preview" style="width: 100%; height: 100%; object-fit: cover; <?= empty($currentCover) ? 'display: none;' : '' ?>">
                        <div id="no_img_text" style="font-size: 0.6875rem; color: var(--text-dim); text-align: center; padding: 0.5rem; <?= !empty($currentCover) ? 'display: none;' : '' ?>">
                            📷 No Cover Selected
                        </div>
                    </div>

                    <!-- Upload Controls -->
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <div>
                            <label style="display: block; font-size: 0.6875rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-main); margin-bottom: 0.375rem;">
                                Upload Image File (JPG, PNG, WebP)
                            </label>
                            <input type="file" name="cover_image_file" id="cover_image_file" accept="image/jpeg,image/png,image/webp,image/jpg" onchange="previewUploadedImage(this)" class="form-input" style="padding: 0.5rem;">
                        </div>

                        <div>
                            <label style="display: block; font-size: 0.6875rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-dim); margin-bottom: 0.25rem;">
                                Active Path / Selected URL
                            </label>
                            <input type="text" name="cover_image" id="cover_image_path" value="<?= htmlspecialchars($currentCover) ?>" placeholder="/images/guides/story-name.jpg" class="form-input" style="font-size: 0.75rem; font-family: monospace;">
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Dynamic Multiple Sections Builder -->
            <div class="glass-card" style="display: flex; flex-direction: column; gap: 1rem;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <h3 style="font-size: 0.8125rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.06em; color: var(--brown-primary); font-family: 'Space Grotesk', sans-serif;">3. Article Sections (Multiple Descriptions)</h3>
                        <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">Add structured headings, paragraphs, and blueprints</p>
                    </div>
                    <button type="button" onclick="addSection()" class="cream-btn">
                        + Add Section
                    </button>
                </div>

                <div id="sections-wrapper" style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php 
                    $secs = !empty($editPost['sections']) ? $editPost['sections'] : [
                        ['heading' => '1. Executive Unit Economics & Thesis', 'content' => ''],
                        ['heading' => '2. Tactical Implementation Blueprint', 'content' => '']
                    ];
                    foreach ($secs as $idx => $s): 
                    ?>
                        <div class="section-block" style="padding: 1.125rem; border-radius: 0.875rem; background: var(--bg-subtle); border: 1px solid var(--border-subtle); display: flex; flex-direction: column; gap: 0.75rem;">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <span style="font-size: 0.625rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; color: var(--brown-primary);">Section Block</span>
                                <button type="button" onclick="this.closest('.section-block').remove()" style="font-size: 0.6875rem; font-weight: 800; color: var(--red-text); background: none; border: none; cursor: pointer;">Remove ✕</button>
                            </div>
                            <input type="text" name="section_headings[]" value="<?= htmlspecialchars($s['heading'] ?? '') ?>" placeholder="Section Heading" class="form-input">
                            <textarea name="section_contents[]" rows="4" placeholder="Detailed content & paragraphs..." class="form-textarea"><?= htmlspecialchars($s['content'] ?? '') ?></textarea>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 4. Dynamic Multiple FAQs Builder -->
            <div class="glass-card" style="display: flex; flex-direction: column; gap: 1rem;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <h3 style="font-size: 0.8125rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.06em; color: var(--brown-primary); font-family: 'Space Grotesk', sans-serif;">4. Structured FAQs (Multiple Questions)</h3>
                        <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">Add FAQ questions and answers for Google FAQPage Schema</p>
                    </div>
                    <button type="button" onclick="addFaq()" class="cream-btn">
                        + Add FAQ
                    </button>
                </div>

                <div id="faqs-wrapper" style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php 
                    $faqList = !empty($editPost['faqs']) ? $editPost['faqs'] : [
                        ['question' => '', 'answer' => '']
                    ];
                    foreach ($faqList as $idx => $f): 
                    ?>
                        <div class="faq-block" style="padding: 1.125rem; border-radius: 0.875rem; background: var(--bg-subtle); border: 1px solid var(--border-subtle); display: flex; flex-direction: column; gap: 0.75rem;">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <span style="font-size: 0.625rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; color: var(--brown-primary);">FAQ Item</span>
                                <button type="button" onclick="this.closest('.faq-block').remove()" style="font-size: 0.6875rem; font-weight: 800; color: var(--red-text); background: none; border: none; cursor: pointer;">Remove ✕</button>
                            </div>
                            <input type="text" name="faq_questions[]" value="<?= htmlspecialchars($f['question'] ?? '') ?>" placeholder="Frequently Asked Question" class="form-input">
                            <textarea name="faq_answers[]" rows="2" placeholder="Accurate, concise answer..." class="form-textarea"><?= htmlspecialchars($f['answer'] ?? '') ?></textarea>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" class="brown-btn" style="width: 100%; padding: 0.95rem; font-size: 0.875rem;">
                Save & Publish Story to Database →
            </button>
        </form>
    </div>

    <!-- Media Library Selection Modal -->
    <div id="mediaModal" style="display: none; position: fixed; inset: 0; background: rgba(20, 14, 10, 0.7); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center; padding: 1.5rem;">
        <div class="glass-card" style="max-width: 48rem; width: 100%; max-height: 80vh; overflow-y: auto; background: #ffffff; display: flex; flex-direction: column; gap: 1rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-subtle); pb: 0.75rem;">
                <h3 style="font-size: 1rem; font-weight: 900; color: var(--text-main); font-family: 'Space Grotesk', sans-serif;">Select Image from Media Library</h3>
                <button type="button" onclick="closeMediaModal()" style="font-size: 1rem; font-weight: 800; color: var(--text-muted); background: none; border: none; cursor: pointer;">✕</button>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 1rem; padding: 0.5rem 0;">
                <?php foreach ($availableMedia as $m): ?>
                    <div onclick="selectMediaImage('<?= htmlspecialchars($m['file_path']) ?>')" style="aspect-ratio: 16/9; border-radius: 0.5rem; overflow: hidden; border: 2px solid var(--border-subtle); cursor: pointer; transition: all 0.2s; position: relative;" onmouseover="this.style.borderColor='var(--brown-primary)'; this.style.transform='scale(1.03)';" onmouseout="this.style.borderColor='var(--border-subtle)'; this.style.transform='scale(1)';">
                        <img src="<?= htmlspecialchars($m['file_path']) ?>" alt="<?= htmlspecialchars($m['alt_text'] ?? '') ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <span style="position: absolute; bottom: 0.25rem; right: 0.25rem; background: rgba(0,0,0,0.7); color: #fff; font-size: 0.5625rem; padding: 0.1rem 0.3rem; border-radius: 0.2rem;">Select</span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="display: flex; justify-content: flex-end;">
                <button type="button" onclick="closeMediaModal()" class="cream-btn">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <!-- Dynamic JavaScript Builders & Image Handlers -->
    <script>
        function previewUploadedImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('cover_img_preview');
                    const noText = document.getElementById('no_img_text');
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    if (noText) noText.style.display = 'none';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function openMediaModal() {
            document.getElementById('mediaModal').style.display = 'flex';
        }

        function closeMediaModal() {
            document.getElementById('mediaModal').style.display = 'none';
        }

        function selectMediaImage(path) {
            document.getElementById('cover_image_path').value = path;
            const preview = document.getElementById('cover_img_preview');
            const noText = document.getElementById('no_img_text');
            preview.src = path;
            preview.style.display = 'block';
            if (noText) noText.style.display = 'none';
            // Clear file upload input so path is preserved
            document.getElementById('cover_image_file').value = '';
            closeMediaModal();
        }

        function addSection() {
            const container = document.getElementById('sections-wrapper');
            const div = document.createElement('div');
            div.className = 'section-block';
            div.style.cssText = 'padding: 1.125rem; border-radius: 0.875rem; background: var(--bg-subtle); border: 1px solid var(--border-subtle); display: flex; flex-direction: column; gap: 0.75rem;';
            div.innerHTML = `
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-size: 0.625rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; color: var(--brown-primary);">New Section</span>
                    <button type="button" onclick="this.closest('.section-block').remove()" style="font-size: 0.6875rem; font-weight: 800; color: var(--red-text); background: none; border: none; cursor: pointer;">Remove ✕</button>
                </div>
                <input type="text" name="section_headings[]" placeholder="Section Heading" class="form-input">
                <textarea name="section_contents[]" rows="4" placeholder="Detailed content & paragraphs..." class="form-textarea"></textarea>
            `;
            container.appendChild(div);
        }

        function addFaq() {
            const container = document.getElementById('faqs-wrapper');
            const div = document.createElement('div');
            div.className = 'faq-block';
            div.style.cssText = 'padding: 1.125rem; border-radius: 0.875rem; background: var(--bg-subtle); border: 1px solid var(--border-subtle); display: flex; flex-direction: column; gap: 0.75rem;';
            div.innerHTML = `
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-size: 0.625rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; color: var(--brown-primary);">New FAQ Item</span>
                    <button type="button" onclick="this.closest('.faq-block').remove()" style="font-size: 0.6875rem; font-weight: 800; color: var(--red-text); background: none; border: none; cursor: pointer;">Remove ✕</button>
                </div>
                <input type="text" name="faq_questions[]" placeholder="Frequently Asked Question" class="form-input">
                <textarea name="faq_answers[]" rows="2" placeholder="Accurate, concise answer..." class="form-textarea"></textarea>
            `;
            container.appendChild(div);
        }
    </script>

<?php else: ?>
    <!-- Stories List View -->
    <div class="glass-card" style="display: flex; flex-direction: column; gap: 1.25rem;">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h3 style="font-size: 1rem; font-weight: 900; color: var(--text-main); font-family: 'Space Grotesk', sans-serif;">All PR Marketing Stories</h3>
                <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">Total <?= count($posts) ?> stories recorded in database</p>
            </div>
            <a href="/admin/posts.php?action=create" class="brown-btn">
                <span>+</span> Create New Story
            </a>
        </div>

        <div style="overflow-x: auto; border: 1px solid var(--border-subtle); border-radius: 0.75rem;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Story Title</th>
                        <th>Category</th>
                        <th>Reading Time</th>
                        <th>Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts as $p): ?>
                        <tr>
                            <td style="max-width: 320px; padding-right: 1rem;">
                                <div style="font-weight: 800; color: var(--text-main); font-family: 'Space Grotesk', sans-serif; font-size: 0.8125rem;"><?= htmlspecialchars($p['title']) ?></div>
                                <div style="font-size: 0.6875rem; color: var(--text-muted); margin-top: 0.25rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($p['summary'] ?? '') ?></div>
                            </td>
                            <td style="white-space: nowrap;">
                                <span class="badge-brown">
                                    <?= htmlspecialchars($p['category_name'] ?? 'General') ?>
                                </span>
                            </td>
                            <td style="white-space: nowrap; color: var(--text-muted); font-size: 0.75rem;">
                                <?= htmlspecialchars($p['reading_time'] ?? '9 min read') ?>
                            </td>
                            <td style="white-space: nowrap;">
                                <span class="badge-emerald">
                                    <?= htmlspecialchars($p['status']) ?>
                                </span>
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <div style="display: inline-flex; gap: 0.375rem;">
                                    <a href="/startup-stories/<?= htmlspecialchars($p['slug']) ?>/" target="_blank" class="cream-btn" style="padding: 0.35rem 0.65rem; font-size: 0.6875rem;">
                                        View ↗
                                    </a>
                                    <a href="/admin/posts.php?action=edit&id=<?= htmlspecialchars($p['id']) ?>" class="cream-btn" style="padding: 0.35rem 0.65rem; font-size: 0.6875rem;">
                                        Edit
                                    </a>
                                    <a href="/admin/posts.php?action=delete&id=<?= htmlspecialchars($p['id']) ?>" onclick="return confirm('Are you sure you want to delete this story?');" class="cream-btn" style="color: var(--red-text); border-color: var(--red-border); padding: 0.35rem 0.65rem; font-size: 0.6875rem;">
                                        Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php
require_once __DIR__ . '/layout/footer.php';
"""

with open(posts_file, "w", encoding="utf-8") as f:
    f.write(posts_php_content.strip())

print(f"Updated {posts_file} with Image Upload and Media Library Picker!")
