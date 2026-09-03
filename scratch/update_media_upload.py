import os

media_file = r"C:\hk\prmarketing\backend\admin\media.php"

media_php_content = """<?php
/**
 * PR Marketing Ventures — Visual Media Library (Warm Cream & Light Brown)
 */
$pageTitle = "Media Library";
require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/layout/sidebar.php';

require_once __DIR__ . '/../config/database.php';

$db = Database::getConnection();
$msg = '';
$error = '';

// Handle Direct Upload to Media Library
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_media']) && !empty($_FILES['media_file'])) {
    if ($_FILES['media_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['media_file']['tmp_name'];
        $origName = $_FILES['media_file']['name'];
        $fileSize = $_FILES['media_file']['size'];
        $mimeType = $_FILES['media_file']['type'];
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];

        if (in_array($ext, $allowed)) {
            $cleanName = pathinfo($origName, PATHINFO_FILENAME);
            $cleanSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $cleanName)));
            $filename = $cleanSlug . '-' . time() . '.' . $ext;

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
                $filePath = '/images/guides/' . $filename;

                // Register into pr_media_files
                $mediaId = sprintf(
                    '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000,
                    mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );

                $stmt = $db->prepare("INSERT INTO pr_media_files (id, file_name, file_path, file_type, file_size, alt_text, created_at) VALUES (:id, :name, :path, :type, :size, :alt, NOW())");
                $stmt->execute([
                    ':id'   => $mediaId,
                    ':name' => $origName,
                    ':path' => $filePath,
                    ':type' => $mimeType ?: ('image/' . $ext),
                    ':size' => $fileSize,
                    ':alt'  => $cleanName
                ]);

                $msg = "Asset '{$origName}' uploaded and registered successfully!";
            } else {
                $error = "Failed to write file to storage directory.";
            }
        } else {
            $error = "Invalid format. Allowed: JPG, PNG, WEBP, GIF, SVG.";
        }
    } else {
        $error = "Upload failed with error code: " . $_FILES['media_file']['error'];
    }
}

$stmt = $db->query("SELECT * FROM pr_media_files ORDER BY created_at DESC");
$mediaFiles = $stmt->fetchAll();
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

<div class="glass-card" style="display: flex; flex-direction: column; gap: 1.5rem;">
    <!-- Top Bar & Direct Media Upload Form -->
    <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; border-bottom: 1px solid var(--border-subtle); padding-bottom: 1.25rem;">
        <div>
            <h3 style="font-size: 1.125rem; font-weight: 900; color: var(--text-main); font-family: 'Space Grotesk', sans-serif;">Visual Media Suite (4K Assets)</h3>
            <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">Total <?= count($mediaFiles) ?> high-resolution visual assets registered</p>
        </div>

        <form method="POST" enctype="multipart/form-data" style="display: flex; align-items: center; gap: 0.75rem;">
            <input type="hidden" name="upload_media" value="1">
            <input type="file" name="media_file" required accept="image/*" class="form-input" style="padding: 0.4rem 0.75rem; font-size: 0.75rem; max-width: 240px;">
            <button type="submit" class="brown-btn" style="padding: 0.55rem 1rem; font-size: 0.75rem; white-space: nowrap;">
                + Upload New Asset
            </button>
        </form>
    </div>

    <?php if (empty($mediaFiles)): ?>
        <div style="padding: 3rem; text-align: center; font-size: 0.75rem; color: var(--text-muted); background: var(--bg-subtle); border-radius: 0.875rem; border: 1px solid var(--border-subtle);">
            No media assets found in database. Upload visual media using the button above.
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1.25rem;">
            <?php foreach ($mediaFiles as $m): ?>
                <div style="padding: 0.875rem; border-radius: 0.875rem; background: var(--bg-subtle); border: 1px solid var(--border-subtle); display: flex; flex-direction: column; gap: 0.75rem;">
                    <div style="aspect-ratio: 16/9; width: 100%; border-radius: 0.625rem; overflow: hidden; background: #eee5d8; position: relative;">
                        <img src="<?= htmlspecialchars($m['file_path']) ?>" alt="<?= htmlspecialchars($m['alt_text'] ?? '') ?>" style="width: 100%; height: 100%; object-fit: cover;" loading="lazy">
                        <span style="position: absolute; bottom: 0.375rem; right: 0.375rem; padding: 0.125rem 0.375rem; border-radius: 0.25rem; background: rgba(36, 24, 16, 0.85); font-size: 0.625rem; color: #ffffff; font-weight: 800;">
                            4K HD
                        </span>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 0.25rem; overflow: hidden;">
                        <p style="font-size: 0.75rem; font-weight: 800; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($m['file_name']) ?>">
                            <?= htmlspecialchars($m['file_name']) ?>
                        </p>
                        <p style="font-size: 0.625rem; color: var(--text-dim); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-family: monospace;">
                            <?= htmlspecialchars($m['file_path']) ?>
                        </p>
                    </div>
                    <button type="button" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($m['file_path']) ?>'); alert('Image path copied to clipboard!');" class="cream-btn" style="width: 100%; justify-content: center; font-size: 0.6875rem; padding: 0.45rem;">
                        Copy Path 📋
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/layout/footer.php';
"""

with open(media_file, "w", encoding="utf-8") as f:
    f.write(media_php_content.strip())

print(f"Updated {media_file} with Direct Upload Suite!")
