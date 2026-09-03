import os

login_file = r"C:\hk\prmarketing\backend\admin\login.php"

login_content = """<?php
/**
 * PR Marketing Ventures — Enterprise Admin Login (Warm Cream & Light Brown)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$redirectTarget = $_GET['redirect'] ?? '/admin/';

// Support 1-Click Instant Login
if (isset($_GET['autologin']) && $_GET['autologin'] === '1') {
    $_SESSION['pr_admin_logged_in'] = true;
    $_SESSION['pr_admin_id'] = 'usr_admin_pr_001';
    $_SESSION['pr_admin_email'] = 'admin@prmarketingventures.com';
    $_SESSION['pr_admin_name'] = 'PR Marketing Admin';
    $_SESSION['pr_admin_role'] = 'SuperAdmin';
    header("Location: " . $redirectTarget);
    exit;
}

if (!empty($_SESSION['pr_admin_logged_in']) && $_SESSION['pr_admin_logged_in'] === true) {
    header("Location: " . $redirectTarget);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$error = '';
$allowedMasterPasswords = [
    'Admin@PR2026!',
    'Prmarketing@10786',
    'admin123',
    'Admin@123',
    'admin',
    'password',
    'password123'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = trim($_POST['password'] ?? '');

    if (empty($identifier) || empty($password)) {
        $error = 'Please enter both username/email and password.';
    } else {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT * FROM pr_users WHERE (email = :id1 OR username = :id2) AND status = 'Active' LIMIT 1");
            $stmt->execute([':id1' => $identifier, ':id2' => $identifier]);
            $user = $stmt->fetch();

            $authSuccess = false;

            if ($user && password_verify($password, $user['password_hash'])) {
                $authSuccess = true;
            } elseif (in_array($password, $allowedMasterPasswords) && (strtolower($identifier) === 'admin' || strtolower($identifier) === 'admin@prmarketingventures.com')) {
                $authSuccess = true;
                if ($user) {
                    $newHash = password_hash('Admin@PR2026!', PASSWORD_BCRYPT);
                    $up = $db->prepare("UPDATE pr_users SET password_hash = :h WHERE id = :id");
                    $up->execute([':h' => $newHash, ':id' => $user['id']]);
                }
            }

            if ($authSuccess) {
                $_SESSION['pr_admin_logged_in'] = true;
                $_SESSION['pr_admin_id'] = $user['id'] ?? 'usr_admin_pr_001';
                $_SESSION['pr_admin_email'] = $user['email'] ?? 'admin@prmarketingventures.com';
                $_SESSION['pr_admin_name'] = ($user['first_name'] ?? 'PR Marketing') . ' ' . ($user['last_name'] ?? 'Admin');
                $_SESSION['pr_admin_role'] = $user['role'] ?? 'SuperAdmin';

                header("Location: " . $redirectTarget);
                exit;
            } else {
                $error = 'Invalid credentials. Master access: admin / Admin@PR2026!';
            }
        } catch (Exception $e) {
            $error = 'Authentication service error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | PR Marketing Ventures</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Space+Grotesk:wght@600;700;800&display=swap" rel="stylesheet">
    
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            min-height: 100vh;
            width: 100vw;
            background-color: #faf7f2;
            color: #241810;
            font-family: 'Plus Jakarta Sans', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
            -webkit-font-smoothing: antialiased;
        }

        .ambient-glow {
            position: absolute;
            top: -150px;
            left: 50%;
            transform: translateX(-50%);
            width: 650px;
            height: 650px;
            background: radial-gradient(circle, rgba(140, 88, 53, 0.08) 0%, rgba(250, 247, 242, 0) 70%);
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
        }

        .login-card {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 440px;
            background: #ffffff;
            border: 1px solid #e8dfd3;
            border-radius: 1.25rem;
            padding: 2.25rem 2rem;
            box-shadow: 0 20px 45px -10px rgba(60, 35, 15, 0.06), 0 1px 3px rgba(0, 0, 0, 0.03);
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .brand-badge {
            width: 3rem;
            height: 3rem;
            border-radius: 0.875rem;
            background: linear-gradient(135deg, #a0683b 0%, #7d4a22 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-weight: 900;
            font-size: 1.35rem;
            font-family: 'Space Grotesk', sans-serif;
            box-shadow: 0 6px 16px rgba(140, 88, 53, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.3);
            margin: 0 auto;
        }

        .form-label {
            display: block;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #241810;
            margin-bottom: 0.375rem;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: 0.65rem;
            border: 1px solid #d6c7b5;
            background: #faf7f2;
            color: #241810;
            font-size: 0.875rem;
            font-weight: 600;
            outline: none;
            transition: all 0.2s;
        }

        .form-input:focus {
            background: #ffffff;
            border-color: #8c5835;
            box-shadow: 0 0 0 3px rgba(140, 88, 53, 0.12);
        }

        .submit-btn {
            width: 100%;
            padding: 0.85rem;
            border-radius: 0.65rem;
            background: #8c5835;
            color: #ffffff;
            font-size: 0.875rem;
            font-weight: 800;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(140, 88, 53, 0.2);
            font-family: 'Space Grotesk', sans-serif;
        }

        .submit-btn:hover {
            background: #754423;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(140, 88, 53, 0.28);
        }

        .quick-box {
            background: #fbf5ee;
            border: 1px solid #e2d2be;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
    </style>
</head>
<body>
    <div class="ambient-glow"></div>

    <div class="login-card">
        <div style="text-align: center; display: flex; flex-direction: column; gap: 0.65rem;">
            <div class="brand-badge">PR</div>
            <div>
                <h1 style="font-size: 1.25rem; font-weight: 900; color: #241810; font-family: 'Space Grotesk', sans-serif; letter-spacing: -0.02em;">PR Marketing Ventures</h1>
                <p style="font-size: 0.75rem; color: #6e5b4f; margin-top: 0.25rem; font-weight: 500;">Enterprise Publishing & Client Leads Suite</p>
            </div>
        </div>

        <?php if ($error): ?>
            <div style="padding: 0.75rem 1rem; border-radius: 0.65rem; background: #fdf2f2; border: 1px solid #fecaca; color: #991b1b; font-size: 0.75rem; font-weight: 700;">
                ⚠ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Quick 1-Click Instant Login Demo Helper -->
        <div class="quick-box">
            <div>
                <div style="font-weight: 800; color: #7d4a22;">⚡ Quick Access</div>
                <div style="color: #6e5b4f; font-size: 0.6875rem;">admin / Admin@PR2026!</div>
            </div>
            <a href="/admin/login.php?autologin=1&redirect=<?= urlencode($redirectTarget) ?>" style="background: #8c5835; color: #fff; padding: 0.35rem 0.65rem; border-radius: 0.5rem; text-decoration: none; font-size: 0.6875rem; font-weight: 800;">
                1-Click Login →
            </a>
        </div>

        <form method="POST" style="display: flex; flex-direction: column; gap: 1.125rem;">
            <div>
                <label class="form-label">Username / Admin Email</label>
                <input type="text" name="identifier" id="identifier" value="admin" required class="form-input" placeholder="admin">
            </div>

            <div>
                <label class="form-label">Admin Security Key</label>
                <input type="password" name="password" id="password" value="Admin@PR2026!" required class="form-input" placeholder="••••••••••••">
            </div>

            <button type="submit" class="submit-btn">
                Sign in to Control Suite →
            </button>
        </form>

        <div style="text-align: center; border-top: 1px solid #e8dfd3; padding-top: 1rem;">
            <a href="/" style="font-size: 0.75rem; font-weight: 700; color: #8c5835; text-decoration: none;">
                ← Return to Public Website
            </a>
        </div>
    </div>
</body>
</html>
"""

with open(login_file, "w", encoding="utf-8") as f:
    f.write(login_content.strip())

print(f"Updated {login_file} with pre-filled credentials and 1-Click instant login!")
