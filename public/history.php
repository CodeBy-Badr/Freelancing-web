<?php
/**
 * History Page (history.php)
 * Paginated list of all code reviews and interview sessions for the logged-in user.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/classes/Auth.php';

$auth = new Auth();
$auth->requireAuth();

$userId = $auth->userId();
$db     = Database::getInstance();

// ─── Pagination & Tab ─────────────────────────────────────────────────────────
$tab     = in_array($_GET['tab'] ?? 'reviews', ['reviews', 'interviews']) ? $_GET['tab'] : 'reviews';
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset  = ($page - 1) * $perPage;

if ($tab === 'reviews') {
    $total = (int)$db->fetchOne('SELECT COUNT(*) as c FROM code_reviews WHERE user_id = :uid', [':uid' => $userId])['c'];
    $items = $db->fetchAll(
        'SELECT id, language, ai_feedback, created_at FROM code_reviews WHERE user_id = :uid ORDER BY created_at DESC LIMIT :limit OFFSET :offset',
        [':uid' => $userId, ':limit' => $perPage, ':offset' => $offset]
    );
} else {
    $total = (int)$db->fetchOne('SELECT COUNT(*) as c FROM interview_sessions WHERE user_id = :uid', [':uid' => $userId])['c'];
    $items = $db->fetchAll(
        'SELECT id, role, final_score, status, created_at FROM interview_sessions WHERE user_id = :uid ORDER BY created_at DESC LIMIT :limit OFFSET :offset',
        [':uid' => $userId, ':limit' => $perPage, ':offset' => $offset]
    );
}

$totalPages = (int)ceil($total / $perPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>History — <?= APP_NAME ?></title>
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
      <a href="dashboard.php"   class="sidebar-link"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>Dashboard</a>
      <a href="reviewer.php"    class="sidebar-link"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>Code Reviewer</a>
      <a href="interviewer.php" class="sidebar-link"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>Mock Interviewer</a>
      <a href="history.php"     class="sidebar-link active"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>History</a>
    </nav>
    <div class="mt-auto">
      <button data-action="logout" class="sidebar-link w-full text-left" style="color:#ef4444;">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        Log Out
      </button>
    </div>
  </aside>

  <!-- ═══ MAIN ═══════════════════════════════════════════════════════════════ -->
  <main class="flex-1 p-6 lg:p-8 overflow-y-auto">

    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold" style="color:#f1f5f9;">📋 History</h1>
        <p style="color:#94a3b8;font-size:0.875rem;">Your complete activity log — <?= $total ?> record<?= $total !== 1 ? 's' : '' ?></p>
      </div>
    </div>

    <!-- Tab Switch -->
    <div class="flex gap-1 p-1 rounded-xl mb-6 w-fit" style="background:#1e293b;">
      <a href="?tab=reviews&page=1"
         class="px-5 py-2 rounded-lg text-sm font-medium transition-all <?= $tab === 'reviews' ? 'text-white' : '' ?>"
         style="<?= $tab === 'reviews' ? 'background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;' : 'color:#94a3b8;' ?>">
        🔍 Code Reviews
      </a>
      <a href="?tab=interviews&page=1"
         class="px-5 py-2 rounded-lg text-sm font-medium transition-all"
         style="<?= $tab === 'interviews' ? 'background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;' : 'color:#94a3b8;' ?>">
        🎤 Interviews
      </a>
    </div>

    <!-- Items List -->
    <?php if (empty($items)): ?>
    <div class="glass-card p-16 text-center">
      <div style="font-size:3rem;margin-bottom:1rem;"><?= $tab === 'reviews' ? '📄' : '🎤' ?></div>
      <p style="color:#94a3b8;font-size:0.9rem;">No <?= $tab ?> found yet.</p>
      <a href="<?= $tab === 'reviews' ? 'reviewer.php' : 'interviewer.php' ?>" class="btn-gradient inline-block mt-4 py-2 px-6 text-sm">
        <?= $tab === 'reviews' ? 'Start a Review' : 'Start an Interview' ?>
      </a>
    </div>
    <?php else: ?>

    <div class="space-y-3">
      <?php foreach ($items as $item): ?>
      <div class="glass-card p-5 fade-in-up">
        <?php if ($tab === 'reviews'): ?>
        <!-- Code Review Row -->
        <div class="flex items-start gap-4">
          <span style="background:rgba(99,102,241,0.15);color:#a5b4fc;padding:4px 12px;border-radius:99px;font-size:0.75rem;font-weight:600;flex-shrink:0;margin-top:2px;">
            <?= htmlspecialchars($item['language']) ?>
          </span>
          <div class="flex-1 min-w-0">
            <p style="font-size:0.85rem;color:#94a3b8;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;line-height:1.5;">
              <?= htmlspecialchars(strip_tags(substr($item['ai_feedback'], 0, 200))) ?>…
            </p>
            <p style="font-size:0.72rem;color:#475569;margin-top:6px;">
              <?= date('F j, Y \a\t g:i a', strtotime($item['created_at'])) ?>
            </p>
          </div>
          <span style="font-size:0.7rem;color:#6366f1;white-space:nowrap;">Review #<?= $item['id'] ?></span>
        </div>

        <?php else: ?>
        <!-- Interview Session Row -->
        <?php
          $score = $item['final_score'];
          $level = $score !== null ? ($score >= 7 ? 'high' : ($score >= 4 ? 'medium' : 'low')) : null;
        ?>
        <div class="flex items-center gap-4">
          <?php if ($score !== null && $level): ?>
          <div class="score-badge <?= $level ?>" style="width:44px;height:44px;font-size:0.85rem;flex-shrink:0;">
            <?= number_format((float)$score, 1) ?>
          </div>
          <?php else: ?>
          <div style="width:44px;height:44px;border-radius:50%;background:#1e293b;display:flex;align-items:center;justify-content:center;font-size:0.75rem;color:#475569;flex-shrink:0;">N/A</div>
          <?php endif; ?>
          <div class="flex-1">
            <p style="font-size:0.9rem;font-weight:600;color:#f1f5f9;"><?= htmlspecialchars($item['role']) ?></p>
            <p style="font-size:0.75rem;color:#475569;margin-top:2px;">
              <?= date('F j, Y \a\t g:i a', strtotime($item['created_at'])) ?>
              &nbsp;·&nbsp;
              <span style="color:<?= $item['status'] === 'completed' ? '#10b981' : '#f59e0b' ?>">
                <?= ucfirst($item['status']) ?>
              </span>
            </p>
          </div>
          <?php if ($score !== null): ?>
          <div class="text-right">
            <p style="font-size:1.1rem;font-weight:700;color:<?= $level === 'high' ? '#10b981' : ($level === 'medium' ? '#f59e0b' : '#ef4444') ?>;">
              <?= number_format((float)$score, 1) ?>/10
            </p>
            <p style="font-size:0.7rem;color:#475569;">final score</p>
          </div>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="flex items-center justify-center gap-2 mt-8">
      <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <a href="?tab=<?= $tab ?>&page=<?= $p ?>"
           class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-medium transition-all"
           style="<?= $p === $page
             ? 'background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;'
             : 'background:#1e293b;color:#94a3b8;' ?>">
          <?= $p ?>
        </a>
      <?php endfor; ?>
    </div>
    <?php endif; ?>

    <?php endif; ?>
  </main>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>
