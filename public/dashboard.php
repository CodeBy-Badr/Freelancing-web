<?php
/**
 * Dashboard (dashboard.php) — User overview with stats and recent activity
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/classes/Auth.php';

$auth = new Auth();
$auth->requireAuth();

$userId = $auth->userId();
$db     = Database::getInstance();

// ─── Fetch stats ──────────────────────────────────────────────────────────────
$totalReviews  = $db->fetchOne('SELECT COUNT(*) as c FROM code_reviews WHERE user_id = :uid', [':uid' => $userId])['c'] ?? 0;
$totalSessions = $db->fetchOne('SELECT COUNT(*) as c FROM interview_sessions WHERE user_id = :uid', [':uid' => $userId])['c'] ?? 0;
$avgScore      = $db->fetchOne('SELECT ROUND(AVG(final_score),1) as s FROM interview_sessions WHERE user_id = :uid AND final_score IS NOT NULL', [':uid' => $userId])['s'] ?? 'N/A';
$recentReviews = $db->fetchAll('SELECT id, language, created_at, SUBSTRING(ai_feedback,1,120) as preview FROM code_reviews WHERE user_id = :uid ORDER BY created_at DESC LIMIT 5', [':uid' => $userId]);
$recentSessions= $db->fetchAll('SELECT id, role, final_score, status, created_at FROM interview_sessions WHERE user_id = :uid ORDER BY created_at DESC LIMIT 5', [':uid' => $userId]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES) ?>"/>
  <title>Dashboard — <?= APP_NAME ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="assets/css/app.css"/>
</head>
<body style="background:#0f172a;">

<div class="flex min-h-screen">

  <!-- ═══ SIDEBAR ════════════════════════════════════════════════════════════ -->
  <aside class="sidebar">
    <a href="index.php" class="flex items-center gap-2 text-lg font-bold mb-8 px-2">
      <div style="width:28px;height:28px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:13px;">⚡</div>
      <span class="gradient-text" style="font-size:0.95rem;"><?= APP_NAME ?></span>
    </a>

    <nav class="flex flex-col gap-1">
      <a href="dashboard.php" class="sidebar-link active">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        Dashboard
      </a>
      <a href="reviewer.php" class="sidebar-link">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
        Code Reviewer
      </a>
      <a href="interviewer.php" class="sidebar-link">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
        Mock Interviewer
      </a>
      <a href="history.php" class="sidebar-link">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        History
      </a>
    </nav>

    <div class="mt-auto">
      <div class="glass-card p-3 mb-3">
        <div class="flex items-center gap-2 mb-1">
          <div style="width:32px;height:32px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.75rem;font-weight:700;">
            <?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)) ?>
          </div>
          <div>
            <p style="font-size:0.8rem;font-weight:600;color:#f1f5f9;"><?= htmlspecialchars($_SESSION['username']) ?></p>
            <p style="font-size:0.7rem;color:#94a3b8;">Free Plan</p>
          </div>
        </div>
      </div>
      <button data-action="logout" class="sidebar-link w-full text-left" style="color:#ef4444;">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        Log Out
      </button>
    </div>
  </aside>

  <!-- ═══ MAIN CONTENT ═══════════════════════════════════════════════════════ -->
  <main class="flex-1 p-6 lg:p-8 overflow-y-auto">

    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
      <div>
        <h1 class="text-2xl font-bold" style="color:#f1f5f9;">
          Good <?= (date('H') < 12 ? 'morning' : (date('H') < 17 ? 'afternoon' : 'evening')) ?>, <?= htmlspecialchars($_SESSION['username']) ?> 👋
        </h1>
        <p style="color:#94a3b8;font-size:0.9rem;margin-top:0.25rem;">Here's your progress summary</p>
      </div>
      <div class="hidden md:flex items-center gap-3">
        <a href="reviewer.php"    class="btn-outline text-sm py-2 px-4">🔍 Review Code</a>
        <a href="interviewer.php" class="btn-gradient text-sm py-2 px-4">🎤 Start Interview</a>
      </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
      <div class="stat-card fade-in-up">
        <div class="flex items-center gap-4">
          <div style="width:48px;height:48px;background:rgba(99,102,241,0.15);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.25rem;">🔍</div>
          <div>
            <p style="font-size:2rem;font-weight:800;color:#f1f5f9;"><?= $totalReviews ?></p>
            <p style="font-size:0.8rem;color:#94a3b8;">Code Reviews</p>
          </div>
        </div>
      </div>
      <div class="stat-card fade-in-up" style="animation-delay:0.1s;">
        <div class="flex items-center gap-4">
          <div style="width:48px;height:48px;background:rgba(139,92,246,0.15);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.25rem;">🎤</div>
          <div>
            <p style="font-size:2rem;font-weight:800;color:#f1f5f9;"><?= $totalSessions ?></p>
            <p style="font-size:0.8rem;color:#94a3b8;">Interview Sessions</p>
          </div>
        </div>
      </div>
      <div class="stat-card fade-in-up" style="animation-delay:0.2s;">
        <div class="flex items-center gap-4">
          <div style="width:48px;height:48px;background:rgba(6,182,212,0.15);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.25rem;">📊</div>
          <div>
            <p style="font-size:2rem;font-weight:800;color:#f1f5f9;"><?= $avgScore !== 'N/A' ? $avgScore . '/10' : 'N/A' ?></p>
            <p style="font-size:0.8rem;color:#94a3b8;">Avg Interview Score</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Activity Grid -->
    <div class="grid lg:grid-cols-2 gap-6">

      <!-- Recent Code Reviews -->
      <div class="glass-card p-5">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-bold text-base" style="color:#f1f5f9;">Recent Code Reviews</h2>
          <a href="reviewer.php" class="text-xs" style="color:#6366f1;">+ New Review</a>
        </div>
        <?php if (empty($recentReviews)): ?>
          <div class="text-center py-8" style="color:#475569;">
            <div style="font-size:2rem;margin-bottom:0.5rem;">📄</div>
            <p style="font-size:0.875rem;">No reviews yet. <a href="reviewer.php" style="color:#6366f1;">Start one →</a></p>
          </div>
        <?php else: ?>
          <div class="space-y-3">
            <?php foreach ($recentReviews as $r): ?>
            <div class="p-3 rounded-lg" style="background:#0f172a;border:1px solid #1e293b;">
              <div class="flex items-center justify-between mb-1">
                <span style="font-size:0.75rem;background:rgba(99,102,241,0.15);color:#a5b4fc;padding:2px 8px;border-radius:99px;font-weight:600;"><?= htmlspecialchars($r['language']) ?></span>
                <span style="font-size:0.7rem;color:#475569;"><?= date('M j, g:i a', strtotime($r['created_at'])) ?></span>
              </div>
              <p style="font-size:0.8rem;color:#94a3b8;margin-top:4px;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">
                <?= htmlspecialchars(strip_tags($r['preview'])) ?>…
              </p>
            </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Recent Interview Sessions -->
      <div class="glass-card p-5">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-bold text-base" style="color:#f1f5f9;">Recent Interviews</h2>
          <a href="interviewer.php" class="text-xs" style="color:#6366f1;">+ New Interview</a>
        </div>
        <?php if (empty($recentSessions)): ?>
          <div class="text-center py-8" style="color:#475569;">
            <div style="font-size:2rem;margin-bottom:0.5rem;">🎤</div>
            <p style="font-size:0.875rem;">No interviews yet. <a href="interviewer.php" style="color:#6366f1;">Start one →</a></p>
          </div>
        <?php else: ?>
          <div class="space-y-3">
            <?php foreach ($recentSessions as $s):
              $score = $s['final_score'];
              $level = $score !== null ? ($score >= 7 ? 'high' : ($score >= 4 ? 'medium' : 'low')) : 'medium';
            ?>
            <div class="p-3 rounded-lg flex items-center gap-3" style="background:#0f172a;border:1px solid #1e293b;">
              <?php if ($score !== null): ?>
              <div class="score-badge <?= $level ?>" style="width:40px;height:40px;font-size:0.8rem;flex-shrink:0;">
                <?= number_format((float)$score, 1) ?>
              </div>
              <?php else: ?>
              <div style="width:40px;height:40px;border-radius:50%;background:#1e293b;display:flex;align-items:center;justify-content:center;font-size:0.7rem;color:#475569;flex-shrink:0;">—</div>
              <?php endif; ?>
              <div>
                <p style="font-size:0.85rem;font-weight:600;color:#f1f5f9;"><?= htmlspecialchars($s['role']) ?></p>
                <p style="font-size:0.7rem;color:#475569;"><?= date('M j, Y', strtotime($s['created_at'])) ?> · <span style="color:<?= $s['status'] === 'completed' ? '#10b981' : '#f59e0b' ?>"><?= ucfirst($s['status']) ?></span></p>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="mt-6 grid sm:grid-cols-2 gap-4">
      <a href="reviewer.php" class="glass-card p-5 flex items-center gap-4 hover:border-indigo-500 transition-colors no-underline">
        <div style="width:44px;height:44px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.25rem;flex-shrink:0;">🔍</div>
        <div>
          <p style="font-weight:700;color:#f1f5f9;font-size:0.95rem;">AI Code Reviewer</p>
          <p style="color:#94a3b8;font-size:0.8rem;">Paste code, get expert feedback instantly</p>
        </div>
        <svg class="ml-auto" style="color:#6366f1;width:20px;height:20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
      </a>
      <a href="interviewer.php" class="glass-card p-5 flex items-center gap-4 hover:border-violet-500 transition-colors no-underline">
        <div style="width:44px;height:44px;background:linear-gradient(135deg,#8b5cf6,#06b6d4);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.25rem;flex-shrink:0;">🎤</div>
        <div>
          <p style="font-weight:700;color:#f1f5f9;font-size:0.95rem;">Mock Interviewer</p>
          <p style="color:#94a3b8;font-size:0.8rem;">Practice technical interviews with AI scoring</p>
        </div>
        <svg class="ml-auto" style="color:#8b5cf6;width:20px;height:20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
      </a>
    </div>
  </main>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>
