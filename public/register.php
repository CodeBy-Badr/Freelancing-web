<?php
/**
 * Register Page (register.php)
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
  <title>Create Account — <?= APP_NAME ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="assets/css/app.css"/>
</head>
<body class="hero-bg flex items-center justify-center min-h-screen px-4 py-10">

  <div class="w-full max-w-md fade-in-up">
    <!-- Logo -->
    <div class="text-center mb-8">
      <a href="index.php" class="inline-flex items-center gap-2 text-2xl font-bold">
        <div style="width:36px;height:36px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:10px;display:flex;align-items:center;justify-content:center;">⚡</div>
        <span class="gradient-text"><?= APP_NAME ?></span>
      </a>
      <p class="mt-2 text-sm" style="color:#94a3b8;">Create your free account — no credit card needed</p>
    </div>

    <div class="glass-card p-8">
      <h1 class="text-2xl font-bold mb-6" style="color:#f1f5f9;">Get Started Free</h1>

      <div id="auth-error"   class="alert alert-error   hidden"></div>
      <div id="auth-success" class="alert alert-success hidden"></div>

      <form id="register-form" novalidate>
        <!-- Username -->
        <div class="mb-4">
          <label class="block text-sm font-medium mb-2" style="color:#cbd5e1;" for="username">Username</label>
          <input type="text" id="username" name="username" required minlength="3" maxlength="50"
                 class="form-input" placeholder="johndoe"/>
        </div>

        <!-- Email -->
        <div class="mb-4">
          <label class="block text-sm font-medium mb-2" style="color:#cbd5e1;" for="email">Email address</label>
          <input type="email" id="email" name="email" required autocomplete="email"
                 class="form-input" placeholder="you@example.com"/>
        </div>

        <!-- Password -->
        <div class="mb-4">
          <label class="block text-sm font-medium mb-2" style="color:#cbd5e1;" for="password">Password</label>
          <div class="relative">
            <input type="password" id="password" name="password" required
                   class="form-input pr-11" placeholder="Min. 8 chars with uppercase, number, symbol"/>
            <button type="button" id="toggle-pw" class="absolute right-3 top-1/2 -translate-y-1/2"
                    style="color:#94a3b8;background:none;border:none;cursor:pointer;">👁️</button>
          </div>
          <!-- Strength meter -->
          <div class="mt-2">
            <div style="height:4px;background:#1e293b;border-radius:2px;">
              <div id="strength-bar" style="height:100%;border-radius:2px;transition:width 0.3s,background 0.3s;width:0;"></div>
            </div>
            <p id="strength-label" class="text-xs mt-1" style="color:#94a3b8;"></p>
          </div>
        </div>

        <!-- Confirm Password -->
        <div class="mb-6">
          <label class="block text-sm font-medium mb-2" style="color:#cbd5e1;" for="confirm-password">Confirm Password</label>
          <input type="password" id="confirm-password" name="confirm_password" required
                 class="form-input" placeholder="••••••••"/>
        </div>

        <!-- Terms -->
        <div class="flex items-start gap-3 mb-6">
          <input type="checkbox" id="terms" class="mt-1 w-4 h-4 accent-indigo-500" required/>
          <label for="terms" class="text-sm" style="color:#94a3b8;">
            I agree to the <a href="#" style="color:#6366f1;">Terms of Service</a> and <a href="#" style="color:#6366f1;">Privacy Policy</a>
          </label>
        </div>

        <button type="submit" id="register-btn" class="btn-gradient w-full py-3 flex items-center justify-center gap-2">
          Create Account
        </button>
      </form>

      <p class="text-center text-sm mt-6" style="color:#94a3b8;">
        Already have an account? <a href="login.php" style="color:#6366f1;font-weight:600;">Log in</a>
      </p>
    </div>

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

    // ─── Password Strength ────────────────────────────────────────────────────
    document.getElementById('password').addEventListener('input', function () {
      const v = this.value;
      let score = 0;
      if (v.length >= 8)        score++;
      if (/[A-Z]/.test(v))      score++;
      if (/[0-9]/.test(v))      score++;
      if (/[\W_]/.test(v))      score++;
      if (v.length >= 14)       score++;

      const bar    = document.getElementById('strength-bar');
      const label  = document.getElementById('strength-label');
      const levels = [
        { w: '0%',   bg: 'transparent', text: '' },
        { w: '25%',  bg: '#ef4444',     text: '🔴 Weak' },
        { w: '50%',  bg: '#f59e0b',     text: '🟡 Fair' },
        { w: '75%',  bg: '#6366f1',     text: '🔵 Good' },
        { w: '90%',  bg: '#8b5cf6',     text: '🟣 Strong' },
        { w: '100%', bg: '#10b981',     text: '🟢 Very Strong' },
      ];
      bar.style.width      = levels[score].w;
      bar.style.background = levels[score].bg;
      label.textContent    = levels[score].text;
    });

    // ─── Register Form ────────────────────────────────────────────────────────
    document.getElementById('register-form').addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn   = document.getElementById('register-btn');
      const errEl = document.getElementById('auth-error');
      const sucEl = document.getElementById('auth-success');
      errEl.classList.add('hidden');
      sucEl.classList.add('hidden');

      const password = document.getElementById('password').value;
      const confirm  = document.getElementById('confirm-password').value;
      const terms    = document.getElementById('terms').checked;

      if (password !== confirm) {
        errEl.innerHTML = '⚠️ Passwords do not match.';
        errEl.classList.remove('hidden');
        return;
      }
      if (!terms) {
        errEl.innerHTML = '⚠️ Please accept the Terms of Service.';
        errEl.classList.remove('hidden');
        return;
      }

      btn.disabled = true;
      btn.innerHTML = '<span class="spinner"></span><span>Creating account…</span>';

      try {
        const res  = await fetch('/api/auth.php', {
          method:  'POST',
          headers: { 'Content-Type': 'application/json' },
          body:    JSON.stringify({
            action:   'register',
            username: document.getElementById('username').value.trim(),
            email:    document.getElementById('email').value.trim(),
            password,
          }),
        });
        const data = await res.json();

        if (data.success) {
          sucEl.innerHTML = '✅ Account created! Redirecting to login…';
          sucEl.classList.remove('hidden');
          setTimeout(() => window.location.href = 'login.php', 1500);
        } else {
          errEl.innerHTML = '⚠️ ' + (data.error || 'Registration failed.');
          errEl.classList.remove('hidden');
          btn.disabled = false;
          btn.textContent = 'Create Account';
        }
      } catch (_) {
        errEl.innerHTML = '⚠️ Network error. Please try again.';
        errEl.classList.remove('hidden');
        btn.disabled = false;
        btn.textContent = 'Create Account';
      }
    });
  </script>
</body>
</html>
