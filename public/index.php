<?php
/**
 * Landing Page (index.php)
 * Public-facing marketing page with hero, features, and CTA sections.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/classes/Auth.php';

$auth = new Auth();
// Redirect logged-in users straight to dashboard
if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="AI-powered portfolio optimizer and mock technical interviewer for web developers and freelancers." />
  <title><?= APP_NAME ?> — Supercharge Your Dev Career</title>

  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Custom styles -->
  <link rel="stylesheet" href="assets/css/app.css" />

  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            brand: { primary: '#6366f1', secondary: '#8b5cf6', accent: '#06b6d4' }
          }
        }
      }
    };
  </script>
</head>
<body class="hero-bg">

  <!-- ═══ NAVBAR ═══════════════════════════════════════════════════════════════ -->
  <nav class="sticky top-0 z-50" style="background:rgba(15,23,42,0.85);backdrop-filter:blur(16px);border-bottom:1px solid rgba(99,102,241,0.15);">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between">
      <!-- Logo -->
      <a href="index.php" class="flex items-center gap-2 text-xl font-bold">
        <div style="width:32px;height:32px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:16px;">⚡</div>
        <span class="gradient-text"><?= APP_NAME ?></span>
      </a>

      <!-- Desktop Nav -->
      <div class="hidden md:flex items-center gap-1">
        <a href="#features"    class="nav-link">Features</a>
        <a href="#how-it-works" class="nav-link">How It Works</a>
        <a href="#pricing"     class="nav-link">Pricing</a>
      </div>

      <!-- Auth Links -->
      <div id="auth-section" class="flex items-center gap-3">
        <a href="login.php"    class="btn-outline text-sm py-2 px-4">Log In</a>
        <a href="register.php" class="btn-gradient text-sm py-2 px-4">Get Started Free</a>
      </div>

      <!-- Mobile toggle -->
      <button id="nav-toggle" class="md:hidden p-2 rounded-lg" style="color:#94a3b8;">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
      </button>
    </div>
  </nav>

  <!-- ═══ HERO ═════════════════════════════════════════════════════════════════ -->
  <section class="max-w-7xl mx-auto px-4 sm:px-6 pt-24 pb-20 text-center">
    <!-- Badge -->
    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full mb-8 fade-in-up text-sm font-medium"
         style="background:rgba(99,102,241,0.12);border:1px solid rgba(99,102,241,0.3);color:#a5b4fc;">
      ✨ Powered by Google Gemini AI
    </div>

    <h1 class="text-5xl sm:text-6xl lg:text-7xl font-extrabold leading-tight mb-6 fade-in-up" style="animation-delay:0.1s;">
      Level Up Your<br>
      <span class="gradient-text">Dev Career</span><br>
      with AI
    </h1>

    <p class="text-lg sm:text-xl max-w-2xl mx-auto mb-10 fade-in-up" style="color:#94a3b8;animation-delay:0.2s;">
      Review your code with a senior engineer's eye, then ace technical interviews
      with an AI mock interviewer that scores and coaches you in real time.
    </p>

    <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-16 fade-in-up" style="animation-delay:0.3s;">
      <a href="register.php" class="btn-gradient text-base py-3 px-8 pulse-glow">
        🚀 Start for Free
      </a>
      <a href="#features" class="btn-outline text-base py-3 px-8">
        See Features →
      </a>
    </div>

    <!-- Hero Preview Card -->
    <div class="glass-card p-1 max-w-4xl mx-auto fade-in-up" style="animation-delay:0.4s;">
      <div style="background:#0d1117;border-radius:0.875rem;padding:1.5rem;text-align:left;">
        <div class="flex items-center gap-2 mb-4">
          <div style="width:12px;height:12px;border-radius:50%;background:#ef4444;"></div>
          <div style="width:12px;height:12px;border-radius:50%;background:#f59e0b;"></div>
          <div style="width:12px;height:12px;border-radius:50%;background:#10b981;"></div>
          <span style="margin-left:8px;font-size:0.75rem;color:#475569;font-family:'JetBrains Mono',monospace;">AI Code Review — app.js</span>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;font-family:'JetBrains Mono',monospace;font-size:0.78rem;">
          <div>
            <div style="color:#6b7280;margin-bottom:0.5rem;font-size:0.7rem;text-transform:uppercase;letter-spacing:0.05em;">Your Code</div>
            <pre style="color:#e6edf3;white-space:pre-wrap;"><span style="color:#ff7b72;">function</span> <span style="color:#d2a8ff;">fetchData</span>() {
  <span style="color:#ff7b72;">var</span> data = [];
  <span style="color:#ff7b72;">for</span>(var i=0;i&lt;100;i++){
    data.push(fetch(<span style="color:#a5d6ff;">'/api/'+</span>i));
  }
  <span style="color:#ff7b72;">return</span> data;
}</pre>
          </div>
          <div>
            <div style="color:#6b7280;margin-bottom:0.5rem;font-size:0.7rem;text-transform:uppercase;letter-spacing:0.05em;">AI Feedback</div>
            <div style="color:#94a3b8;font-size:0.75rem;line-height:1.6;font-family:'Inter',sans-serif;">
              <p style="color:#f87171;font-weight:600;">⚠️ Performance Issue</p>
              <p>100 sequential fetch calls — use <code style="color:#a5b4fc;">Promise.all()</code> for parallel requests.</p>
              <br>
              <p style="color:#6ee7b7;font-weight:600;">✨ Refactored</p>
              <p>Replace <code style="color:#a5b4fc;">var</code> with <code style="color:#a5b4fc;">const</code>, use async/await pattern.</p>
              <br>
              <p style="color:#fbbf24;font-weight:600;">📊 Score: <strong>4/10</strong></p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ═══ FEATURES ═════════════════════════════════════════════════════════════ -->
  <section id="features" class="max-w-7xl mx-auto px-4 sm:px-6 py-20">
    <div class="text-center mb-14">
      <h2 class="text-4xl font-bold mb-4">Everything You Need to <span class="gradient-text">Grow Faster</span></h2>
      <p style="color:#94a3b8;max-width:500px;margin:0 auto;">Two powerful AI tools, one streamlined platform built for serious developers.</p>
    </div>

    <div class="grid md:grid-cols-3 gap-6">
      <?php
      $features = [
        ['icon'=>'🔍','title'=>'AI Code Reviewer','color'=>'#6366f1','desc'=>'Paste any HTML, CSS, JS, TypeScript, or PHP snippet. Get a senior engineer\'s audit in seconds — performance, security, and clean refactored code.'],
        ['icon'=>'🎤','title'=>'Mock Interviewer','color'=>'#8b5cf6','desc'=>'Choose your role and start a real-time AI interview. Get scored on JS closures, PHP OOP, MySQL, and more with constructive feedback after every answer.'],
        ['icon'=>'📊','title'=>'Progress Dashboard','color'=>'#06b6d4','desc'=>'Track every review and interview session. See your improvement over time with detailed score history and transcript archive.'],
      ];
      foreach ($features as $f): ?>
      <div class="glass-card p-6 text-center">
        <div style="width:56px;height:56px;background:rgba(<?= hexToRgb($f['color']) ?>,0.15);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;margin:0 auto 1rem;">
          <?= $f['icon'] ?>
        </div>
        <h3 class="text-lg font-bold mb-2" style="color:#f1f5f9;"><?= $f['title'] ?></h3>
        <p style="color:#94a3b8;font-size:0.9rem;line-height:1.6;"><?= $f['desc'] ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- ═══ HOW IT WORKS ══════════════════════════════════════════════════════════ -->
  <section id="how-it-works" class="max-w-7xl mx-auto px-4 sm:px-6 py-20">
    <div class="text-center mb-14">
      <h2 class="text-4xl font-bold mb-4"><span class="gradient-text">3 Steps</span> to Better Code</h2>
    </div>
    <div class="grid md:grid-cols-3 gap-8">
      <?php
      $steps = [
        ['step'=>'01','title'=>'Create Your Account','desc'=>'Sign up for free in 30 seconds. No credit card required.'],
        ['step'=>'02','title'=>'Paste Code or Start Interview','desc'=>'Use the Code Reviewer to get instant AI feedback, or launch a mock interview session.'],
        ['step'=>'03','title'=>'Improve & Track Progress','desc'=>'Review your AI feedback, study your scores, and come back to practice daily.'],
      ];
      foreach ($steps as $s): ?>
      <div class="relative">
        <div class="stat-card text-center">
          <div class="text-5xl font-black mb-4 gradient-text opacity-30"><?= $s['step'] ?></div>
          <h3 class="text-lg font-bold mb-2" style="color:#f1f5f9;"><?= $s['title'] ?></h3>
          <p style="color:#94a3b8;font-size:0.9rem;"><?= $s['desc'] ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- ═══ PRICING ═══════════════════════════════════════════════════════════════ -->
  <section id="pricing" class="max-w-7xl mx-auto px-4 sm:px-6 py-20">
    <div class="text-center mb-14">
      <h2 class="text-4xl font-bold mb-4">Simple, <span class="gradient-text">Transparent</span> Pricing</h2>
    </div>
    <div class="grid md:grid-cols-2 gap-6 max-w-3xl mx-auto">
      <!-- Free -->
      <div class="glass-card p-8">
        <h3 class="text-xl font-bold mb-2" style="color:#f1f5f9;">Free</h3>
        <div class="text-4xl font-black gradient-text mb-4">$0<span class="text-lg font-normal" style="color:#94a3b8;">/mo</span></div>
        <ul class="space-y-3 mb-8" style="color:#94a3b8;font-size:0.9rem;">
          <li>✅ 20 AI code reviews / day</li>
          <li>✅ 10 interview sessions / day</li>
          <li>✅ Session transcript history</li>
          <li>❌ Priority AI processing</li>
        </ul>
        <a href="register.php" class="btn-outline w-full text-center block">Get Started</a>
      </div>
      <!-- Pro -->
      <div class="glass-card p-8" style="border-color:rgba(99,102,241,0.5);">
        <div class="flex items-center gap-2 mb-2">
          <h3 class="text-xl font-bold" style="color:#f1f5f9;">Pro</h3>
          <span style="background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;font-size:0.7rem;padding:2px 8px;border-radius:99px;font-weight:600;">POPULAR</span>
        </div>
        <div class="text-4xl font-black gradient-text mb-4">$12<span class="text-lg font-normal" style="color:#94a3b8;">/mo</span></div>
        <ul class="space-y-3 mb-8" style="color:#94a3b8;font-size:0.9rem;">
          <li>✅ Unlimited code reviews</li>
          <li>✅ Unlimited interviews</li>
          <li>✅ Full transcript export</li>
          <li>✅ Priority AI processing</li>
        </ul>
        <a href="register.php" class="btn-gradient w-full text-center block">Start Pro Trial</a>
      </div>
    </div>
  </section>

  <!-- ═══ CTA FOOTER ════════════════════════════════════════════════════════════ -->
  <section class="max-w-7xl mx-auto px-4 sm:px-6 py-16 text-center">
    <div class="glass-card p-12">
      <h2 class="text-4xl font-bold mb-4">Ready to <span class="gradient-text">Transform</span> Your Career?</h2>
      <p style="color:#94a3b8;margin-bottom:2rem;">Join developers already levelling up with AI-powered feedback.</p>
      <a href="register.php" class="btn-gradient text-lg py-4 px-10 pulse-glow">
        🚀 Create Free Account
      </a>
    </div>
  </section>

  <!-- Footer -->
  <footer class="border-t py-8 text-center text-sm" style="border-color:#1e293b;color:#475569;">
    <p>© <?= date('Y') ?> <?= APP_NAME ?>. Built with ❤️ using PHP, Tailwind CSS & Gemini AI.</p>
  </footer>

  <script src="assets/js/app.js"></script>
</body>
</html>

<?php
function hexToRgb(string $hex): string {
    $hex = ltrim($hex, '#');
    [$r, $g, $b] = [hexdec(substr($hex,0,2)), hexdec(substr($hex,2,2)), hexdec(substr($hex,4,2))];
    return "$r,$g,$b";
}
?>
