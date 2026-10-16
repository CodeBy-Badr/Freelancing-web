<?php
/**
 * Login Page (login.php)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/classes/Auth.php';

$auth = new Auth();
if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Log In — <?= APP_NAME ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="assets/css/app.css"/>
</head>
<body class="hero-bg flex items-center justify-center min-h-screen px-4">

  <div class="w-full max-w-md fade-in-up">
    <!-- Logo -->
    <div class="text-center mb-8">
      <a href="index.php" class="inline-flex items-center gap-2 text-2xl font-bold">
        <div style="width:36px;height:36px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:10px;display:flex;align-items:center;justify-content:center;">⚡</div>
        <span class="gradient-text"><?= APP_NAME ?></span>
      </a>
      <p class="mt-2 text-sm" style="color:#94a3b8;">Welcome back — log in to your account</p>
    </div>

    <!-- Card -->
    <div class="glass-card p-8">
      <h1 class="text-2xl font-bold mb-6" style="color:#f1f5f9;">Sign In</h1>

      <!-- Error display -->
      <div id="auth-error" class="alert alert-error hidden"></div>
      <div id="auth-success" class="alert alert-success hidden"></div>

      <form id="login-form" novalidate>
        <!-- Email -->
        <div class="mb-5">
          <label class="block text-sm font-medium mb-2" style="color:#cbd5e1;" for="email">Email address</label>
          <input type="email" id="email" name="email" required autocomplete="email"
                 class="form-input" placeholder="you@example.com"/>
        </div>

        <!-- Password -->
        <div class="mb-6">
          <div class="flex justify-between items-center mb-2">
            <label class="text-sm font-medium" style="color:#cbd5e1;" for="password">Password</label>
            <a href="#" class="text-xs" style="color:#6366f1;">Forgot password?</a>
          </div>
          <div class="relative">
            <input type="password" id="password" name="password" required autocomplete="current-password"
                   class="form-input pr-11" placeholder="••••••••"/>
            <button type="button" id="toggle-pw" class="absolute right-3 top-1/2 -translate-y-1/2"
                    style="color:#94a3b8;background:none;border:none;cursor:pointer;font-size:1rem;">👁️</button>
          </div>
        </div>

        <!-- Submit -->
        <button type="submit" id="login-btn" class="btn-gradient w-full py-3 flex items-center justify-center gap-2">
          Log In
        </button>
      </form>

      <!-- Divider -->
      <div class="flex items-center gap-4 my-6">
        <hr style="flex:1;border-color:#334155;"/>
        <span style="color:#475569;font-size:0.8rem;">or</span>
        <hr style="flex:1;border-color:#334155;"/>
      </div>

      <p class="text-center text-sm" style="color:#94a3b8;">
        Don't have an account?
        <a href="register.php" style="color:#6366f1;font-weight:600;">Create one free</a>
      </p>
    </div>

    <!-- Back link -->
    <p class="text-center mt-4 text-sm" style="color:#475569;">
      <a href="index.php" style="color:#6366f1;">← Back to home</a>
    </p>
  </div>

  <script>
    // ─── Password Toggle ──────────────────────────────────────────────────────
    document.getElementById('toggle-pw').addEventListener('click', () => {
      const pw = document.getElementById('password');
      pw.type = pw.type === 'password' ? 'text' : 'password';
    });

    // ─── Login Form ───────────────────────────────────────────────────────────
    document.getElementById('login-form').addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn   = document.getElementById('login-btn');
      const errEl = document.getElementById('auth-error');
      const sucEl = document.getElementById('auth-success');
      errEl.classList.add('hidden');
      sucEl.classList.add('hidden');

      btn.disabled = true;
      btn.innerHTML = '<span class="spinner"></span><span>Signing in…</span>';

      try {
        const res = await fetch('/api/auth.php', {
          method:  'POST',
          headers: { 'Content-Type': 'application/json' },
          body:    JSON.stringify({
            action:   'login',
            email:    document.getElementById('email').value.trim(),
            password: document.getElementById('password').value,
          }),
        });
        const data = await res.json();

        if (data.success) {
          sucEl.textContent = '✅ Logged in! Redirecting…';
          sucEl.classList.remove('hidden');
          // Store CSRF token for subsequent requests
          const meta = document.createElement('meta');
          meta.name = 'csrf-token';
          meta.content = data.csrf_token;
          document.head.appendChild(meta);
          setTimeout(() => window.location.href = data.redirect || 'dashboard.php', 800);
        } else {
          errEl.innerHTML = '⚠️ ' + (data.error || 'Login failed.');
          errEl.classList.remove('hidden');
          btn.disabled = false;
          btn.textContent = 'Log In';
        }
      } catch (_) {
        errEl.innerHTML = '⚠️ Network error. Please try again.';
        errEl.classList.remove('hidden');
        btn.disabled = false;
        btn.textContent = 'Log In';
      }
    });
  </script>
</body>
</html>
