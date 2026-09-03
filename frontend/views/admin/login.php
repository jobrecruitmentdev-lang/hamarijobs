<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../../backend/app/Controllers/AdminController.php';
use App\Controllers\AdminController;

if (AdminController::isAuthenticated()) {
    header('Location: /admin/dashboard');
    exit;
}

$pageTitle = "Admin Authentication — Government Recruitment Intelligence Platform";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🛡️</text></svg>">
  <link rel="stylesheet" href="/assets/css/admin.css">
  <style>
    body {
      background: radial-gradient(circle at 50% 20%, #1e293b 0%, #0f172a 70%, #090d16 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      margin: 0;
      padding: 1.5rem;
    }
    .auth-box {
      width: 100%;
      max-width: 440px;
      background: rgba(255, 255, 255, 0.98);
      border-radius: var(--radius-xl);
      padding: 2.5rem 2rem;
      box-shadow: 0 25px 60px -12px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(255, 255, 255, 0.1);
      position: relative;
    }
    .auth-emblem-wrap {
      width: 60px;
      height: 60px;
      background: linear-gradient(135deg, var(--primary-ruby) 0%, #991b1b 100%);
      border-radius: var(--radius-lg);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.8rem;
      margin: 0 auto 1.25rem;
      box-shadow: 0 8px 24px rgba(220, 38, 38, 0.4);
      border: 1px solid rgba(255, 255, 255, 0.3);
    }
  </style>
</head>
<body>

<div class="auth-box">
  <div style="text-align: center; margin-bottom: 2rem;">
    <div class="auth-emblem-wrap">🛡️</div>
    <h1 style="font-family: var(--font-heading); font-size: 1.6rem; font-weight: 900; color: var(--text-dark); letter-spacing: -0.02em;">
      Admin Command Center
    </h1>
    <p style="font-size: 0.825rem; color: var(--text-muted); margin-top: 0.35rem;">
      Authorized personnel and crawling supervisors access only
    </p>
  </div>

  <!-- Alert Container -->
  <div id="loginAlert" style="display: none; padding: 0.75rem 1rem; border-radius: var(--radius-md); margin-bottom: 1.25rem; font-size: 0.825rem; font-weight: 700;"></div>

  <form id="adminLoginForm">
    <div class="admin-form-group">
      <label for="identity" class="admin-form-label">Operator Email or Username</label>
      <input 
        type="text" 
        id="identity" 
        name="identity" 
        class="admin-form-control" 
        placeholder="admin@jobrecruitai.com" 
        required 
        autocomplete="username"
        value="admin@jobrecruitai.com"
      >
    </div>

    <div class="admin-form-group">
      <label for="password" class="admin-form-label">Security Access Key / Password</label>
      <div style="position: relative;">
        <input 
          type="password" 
          id="password" 
          name="password" 
          class="admin-form-control" 
          placeholder="••••••••••••" 
          required 
          autocomplete="current-password"
          value="Admin@123"
          style="padding-right: 2.75rem;"
        >
        <button 
          type="button" 
          id="togglePasswordBtn" 
          style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 1.1rem; color: var(--text-muted);"
          title="Toggle Password Visibility"
        >
          👁️
        </button>
      </div>
    </div>

    <div style="margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: space-between;">
      <label style="font-size: 0.8rem; color: var(--text-body); display: flex; align-items: center; gap: 0.4rem; cursor: pointer;">
        <input type="checkbox" name="remember" checked style="accent-color: var(--primary-ruby);"> Remember operator session
      </label>
      <span style="font-size: 0.75rem; color: var(--color-emerald); font-weight: 700;">● TLS 256-bit</span>
    </div>

    <button type="submit" id="loginSubmitBtn" class="admin-btn admin-btn-primary" style="width: 100%; padding: 0.75rem; font-size: 0.95rem;">
      🔐 Authenticate & Enter Console &rarr;
    </button>
  </form>

  <div style="margin-top: 1.75rem; padding-top: 1.25rem; border-top: 1px solid var(--border-light); text-align: center;">
    <p style="font-size: 0.775rem; color: var(--text-muted); margin-bottom: 0.4rem;">
      Quick System Operator Credentials:
    </p>
    <div style="display: inline-block; background: var(--admin-surface-subtle); padding: 0.25rem 0.75rem; border-radius: var(--radius-xs); font-size: 0.75rem; color: var(--text-dark); font-family: var(--font-mono); font-weight: 600;">
      admin@jobrecruitai.com / Admin@123
    </div>
    <div style="margin-top: 1.25rem;">
      <a href="/" style="font-size: 0.825rem; color: var(--primary-ruby); font-weight: 700;">
        &larr; Return to Public Portal
      </a>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('adminLoginForm');
  const alertBox = document.getElementById('loginAlert');
  const submitBtn = document.getElementById('loginSubmitBtn');
  const toggleBtn = document.getElementById('togglePasswordBtn');
  const pwdInput = document.getElementById('password');

  if (toggleBtn && pwdInput) {
    toggleBtn.addEventListener('click', () => {
      const isPwd = pwdInput.type === 'password';
      pwdInput.type = isPwd ? 'text' : 'password';
      toggleBtn.innerText = isPwd ? '🔒' : '👁️';
    });
  }

  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      submitBtn.disabled = true;
      submitBtn.innerText = 'Verifying Operator Key...';
      alertBox.style.display = 'none';

      const formData = new FormData(form);
      const data = Object.fromEntries(formData.entries());

      try {
        const response = await fetch('/api/v1/admin/login', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
          alertBox.style.display = 'block';
          alertBox.style.background = '#ecfdf5';
          alertBox.style.color = '#065f46';
          alertBox.style.border = '1px solid #a7f3d0';
          alertBox.innerText = '✓ Authentication confirmed! Redirecting to Control Center...';

          setTimeout(() => {
            window.location.href = result.redirect || '/admin/dashboard';
          }, 600);
        } else {
          alertBox.style.display = 'block';
          alertBox.style.background = '#fef2f2';
          alertBox.style.color = '#991b1b';
          alertBox.style.border = '1px solid #fecaca';
          alertBox.innerText = '✕ ' + (result.error || 'Invalid credentials provided.');
          submitBtn.disabled = false;
          submitBtn.innerText = '🔐 Authenticate & Enter Console →';
        }
      } catch (err) {
        alertBox.style.display = 'block';
        alertBox.style.background = '#fef2f2';
        alertBox.style.color = '#991b1b';
        alertBox.style.border = '1px solid #fecaca';
        alertBox.innerText = '✕ Network error communicating with authentication daemon.';
        submitBtn.disabled = false;
        submitBtn.innerText = '🔐 Authenticate & Enter Console →';
      }
    });
  }
});
</script>

</body>
</html>
