<?php
/**
 * Code Reviewer Page (reviewer.php)
 * Split-pane: code input on left, AI markdown output on right.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/classes/Auth.php';

$auth = new Auth();
$auth->requireAuth();

$csrfToken = $_SESSION['csrf_token'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>"/>
  <title>Code Reviewer — <?= APP_NAME ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="assets/css/app.css"/>
  <!-- Marked.js (Markdown parser) -->
  <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
  <!-- Highlight.js (Syntax highlighting) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css"/>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
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
      <a href="reviewer.php"    class="sidebar-link active"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>Code Reviewer</a>
      <a href="interviewer.php" class="sidebar-link"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>Mock Interviewer</a>
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
  <main class="flex-1 flex flex-col p-4 lg:p-6 overflow-hidden">

    <!-- Top Bar -->
    <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
      <div>
        <h1 class="text-xl font-bold" style="color:#f1f5f9;">🔍 AI Code Reviewer</h1>
        <p style="color:#94a3b8;font-size:0.8rem;">Paste your code below and receive expert AI feedback.</p>
      </div>

      <!-- Controls -->
      <div class="flex items-center gap-3 flex-wrap">
        <!-- Language Selector -->
        <select id="language-select"
                class="form-input text-sm py-2 px-3"
                style="width:auto;background:#1e293b;">
          <option value="javascript">JavaScript</option>
          <option value="typescript">TypeScript</option>
          <option value="php">PHP</option>
          <option value="html">HTML</option>
          <option value="css">CSS</option>
        </select>

        <button id="clear-btn" class="btn-outline text-sm py-2 px-4">Clear</button>

        <button id="copy-btn" class="btn-outline text-sm py-2 px-4" style="display:none;">
          📋 Copy
        </button>

        <button id="review-btn" class="btn-gradient flex items-center gap-2 text-sm py-2 px-5">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
          </svg>
          Review Code
        </button>
      </div>
    </div>

    <!-- Split Pane -->
    <div class="flex-1 grid md:grid-cols-2 gap-4" style="min-height:0;">

      <!-- ─ Left Pane: Code Input ─────────────────────────────────────────── -->
      <div class="flex flex-col glass-card overflow-hidden">
        <!-- Pane Header -->
        <div class="flex items-center justify-between px-4 py-2.5 border-b" style="border-color:#1e293b;">
          <div class="flex items-center gap-2">
            <div style="width:10px;height:10px;border-radius:50%;background:#ef4444;"></div>
            <div style="width:10px;height:10px;border-radius:50%;background:#f59e0b;"></div>
            <div style="width:10px;height:10px;border-radius:50%;background:#10b981;"></div>
          </div>
          <span style="font-size:0.72rem;color:#475569;font-family:'JetBrains Mono',monospace;" id="pane-lang-label">code.js</span>
          <span id="char-count" style="font-size:0.7rem;color:#94a3b8;">0 / 50,000</span>
        </div>

        <!-- Textarea -->
        <textarea
          id="code-input"
          class="code-editor flex-1"
          style="border:none;border-radius:0;"
          placeholder="// Paste your code here...
// Example:
function fetchData() {
  var data = [];
  for(var i = 0; i < 100; i++) {
    data.push(fetch('/api/' + i));
  }
  return data;
}"
          spellcheck="false"
          autocomplete="off"
          autocorrect="off"
          autocapitalize="off"
        ></textarea>

        <!-- Shortcuts hint -->
        <div class="px-4 py-2 border-t" style="border-color:#1e293b;background:#0d1117;">
          <p style="font-size:0.7rem;color:#475569;">
            Press <kbd style="background:#1e293b;border:1px solid #334155;border-radius:3px;padding:1px 5px;">Ctrl</kbd> + <kbd style="background:#1e293b;border:1px solid #334155;border-radius:3px;padding:1px 5px;">Enter</kbd> to review
          </p>
        </div>
      </div>

      <!-- ─ Right Pane: AI Output ─────────────────────────────────────────── -->
      <div class="flex flex-col glass-card overflow-hidden" id="output-pane">
        <!-- Pane Header -->
        <div class="flex items-center justify-between px-4 py-2.5 border-b" style="border-color:#1e293b;">
          <span style="font-size:0.75rem;font-weight:600;color:#94a3b8;">🤖 AI Feedback</span>
          <span style="font-size:0.7rem;color:#475569;">Powered by Gemini AI</span>
        </div>

        <!-- Output Content -->
        <div id="output-content" class="flex-1 p-5 overflow-y-auto">
          <div class="flex flex-col items-center justify-center h-full text-center" style="min-height:300px;color:#94a3b8;">
            <div style="font-size:3rem;margin-bottom:1rem;">🤖</div>
            <p style="font-size:1rem;font-weight:600;color:#cbd5e1;margin-bottom:0.5rem;">AI Review Output</p>
            <p style="font-size:0.85rem;">Paste your code on the left and click<br><strong style="color:#6366f1;">Review Code</strong> to get started.</p>
            <p style="font-size:0.78rem;margin-top:1rem;color:#475569;">
              Shortcut: <kbd style="background:#1e293b;border:1px solid #475569;border-radius:4px;padding:1px 6px;font-size:0.75rem;">Ctrl</kbd> + <kbd style="background:#1e293b;border:1px solid #475569;border-radius:4px;padding:1px 6px;font-size:0.75rem;">Enter</kbd>
            </p>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>

<script src="assets/js/app.js"></script>
<script>
  // Inject CSRF into AppUtils-style calls
  const CSRF_TOKEN = '<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>';
</script>
<script src="assets/js/reviewer.js"></script>
<script>
  // Update lang label on select change
  document.getElementById('language-select').addEventListener('change', function() {
    const ext = { javascript:'js', typescript:'ts', php:'php', html:'html', css:'css' };
    document.getElementById('pane-lang-label').textContent = 'code.' + (ext[this.value] || this.value);
  });
</script>
</body>
</html>
