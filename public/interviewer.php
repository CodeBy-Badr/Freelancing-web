<?php
/**
 * Mock Interviewer Page (interviewer.php)
 * Full chat UI with role selector and score widget.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/classes/Auth.php';

$auth = new Auth();
$auth->requireAuth();

$csrfToken = $_SESSION['csrf_token'] ?? '';

$roles = [
    'Frontend Developer',
    'Backend Engineer',
    'Full-Stack Developer',
    'PHP Developer',
    'React Developer',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>"/>
  <title>Mock Interviewer — <?= APP_NAME ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="assets/css/app.css"/>
  <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
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
      <a href="interviewer.php" class="sidebar-link active"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>Mock Interviewer</a>
      <a href="history.php"     class="sidebar-link"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>History</a>
    </nav>
    <div class="mt-auto">
      <button data-action="logout" class="sidebar-link w-full text-left" style="color:#ef4444;">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        Log Out
      </button>
    </div>
  </aside>

  <!-- ═══ MAIN ═══════════════════════════════════════════════════════════════ -->
  <main class="flex-1 flex flex-col lg:flex-row gap-4 p-4 lg:p-6 overflow-hidden">

    <!-- ─ Left Column: Config + Score ──────────────────────────────────────── -->
    <div class="lg:w-72 flex-shrink-0 flex flex-col gap-4">

      <!-- Session Config Card -->
      <div class="glass-card p-5">
        <h2 class="font-bold text-base mb-4" style="color:#f1f5f9;">🎤 Interview Setup</h2>

        <div class="mb-4">
          <label class="block text-sm font-medium mb-2" style="color:#cbd5e1;">Select Your Role</label>
          <select id="role-select" class="form-input text-sm py-2.5">
            <?php foreach ($roles as $r): ?>
            <option value="<?= htmlspecialchars($r) ?>"><?= htmlspecialchars($r) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Status -->
        <div class="mb-4 p-3 rounded-lg text-sm" style="background:#0f172a;border:1px solid #1e293b;">
          <p id="session-status" style="color:#94a3b8;">⚪ No active session</p>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col gap-2">
          <button id="start-btn" class="btn-gradient w-full py-2.5 text-sm flex items-center justify-center gap-2">
            🚀 Start Interview
          </button>
          <button id="end-btn" class="btn-outline w-full py-2.5 text-sm hidden" style="border-color:#ef4444;color:#ef4444;">
            🛑 End Session
          </button>
        </div>

        <!-- Tips -->
        <div class="mt-4 p-3 rounded-lg text-xs" style="background:rgba(99,102,241,0.08);border:1px solid rgba(99,102,241,0.2);color:#94a3b8;line-height:1.6;">
          <p style="color:#a5b4fc;font-weight:600;margin-bottom:4px;">💡 Tips</p>
          <ul style="padding-left:12px;list-style:disc;">
            <li>Answer naturally, as in a real interview.</li>
            <li>The AI asks 5–6 questions per session.</li>
            <li>Press <kbd style="background:#1e293b;padding:1px 4px;border-radius:3px;">Enter</kbd> to send a message.</li>
            <li>Use <kbd style="background:#1e293b;padding:1px 4px;border-radius:3px;">Shift+Enter</kbd> for a new line.</li>
          </ul>
        </div>
      </div>

      <!-- Score Display (hidden until session ends) -->
      <div id="score-display" style="display:none;"></div>

    </div>

    <!-- ─ Right Column: Chat ─────────────────────────────────────────────── -->
    <div class="flex-1 flex flex-col glass-card overflow-hidden" style="min-height:0;">

      <!-- Chat Header -->
      <div class="flex items-center justify-between px-5 py-3.5 border-b" style="border-color:#1e293b;">
        <div class="flex items-center gap-3">
          <div style="width:36px;height:36px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1rem;">🤖</div>
          <div>
            <p style="font-weight:700;font-size:0.9rem;color:#f1f5f9;">AI Technical Interviewer</p>
            <p style="font-size:0.7rem;color:#94a3b8;">Powered by Gemini AI</p>
          </div>
        </div>
        <div style="width:8px;height:8px;border-radius:50%;background:#10b981;" id="status-dot" title="Offline"></div>
      </div>

      <!-- Chat Messages -->
      <div id="chat-window" class="flex-1 flex flex-col p-4 gap-1 overflow-y-auto" style="min-height:0;">

        <!-- Welcome Placeholder -->
        <div class="flex flex-col items-center justify-center h-full text-center" style="color:#94a3b8;" id="chat-placeholder">
          <div style="font-size:3.5rem;margin-bottom:1rem;">🎤</div>
          <p style="font-size:1rem;font-weight:600;color:#cbd5e1;margin-bottom:0.5rem;">Ready to Interview?</p>
          <p style="font-size:0.85rem;max-width:280px;">Select your developer role from the left panel and click <strong style="color:#6366f1;">Start Interview</strong> to begin your AI-powered mock session.</p>
        </div>
      </div>

      <!-- Input Area -->
      <div class="border-t p-4" style="border-color:#1e293b;background:#0d1117;">
        <div class="flex gap-3 items-end">
          <textarea
            id="chat-input"
            rows="2"
            disabled
            class="form-input flex-1 resize-none text-sm"
            style="min-height:52px;max-height:120px;"
            placeholder="Start the interview to begin typing your answers…"
          ></textarea>
          <button id="send-btn" disabled
                  class="btn-gradient py-3 px-4 flex-shrink-0 flex items-center justify-center"
                  style="height:52px;width:52px;padding:0;">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
            </svg>
          </button>
        </div>
        <p class="text-xs mt-2" style="color:#475569;">
          <kbd style="background:#1e293b;border:1px solid #334155;border-radius:3px;padding:1px 5px;">Enter</kbd> to send &nbsp;|&nbsp;
          <kbd style="background:#1e293b;border:1px solid #334155;border-radius:3px;padding:1px 5px;">Shift+Enter</kbd> for new line
        </p>
      </div>
    </div>
  </main>
</div>

<script src="assets/js/app.js"></script>
<script>
  const CSRF_TOKEN = '<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>';

  // Remove placeholder when first message arrives
  const origStart = window.startInterview;
</script>
<script src="assets/js/interviewer.js"></script>
<script>
  // Hide placeholder on interview start
  document.getElementById('start-btn').addEventListener('click', () => {
    const ph = document.getElementById('chat-placeholder');
    if (ph) ph.remove();
    document.getElementById('status-dot').title = 'Online';
  });
</script>
</body>
</html>
