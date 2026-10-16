/**
 * reviewer.ts – Code Reviewer page logic
 * Handles split-pane UI, language selection, AI review requests, and markdown rendering.
 */

// ─── Markdown renderer (using marked.js via CDN) ──────────────────────────────
declare const marked: { parse: (md: string) => string };
declare const hljs: { highlightAll: () => void };

const AppUtils = (window as unknown as Record<string, { apiPost: Function; showToast: Function; getCsrfToken: Function }>).AppUtils;

// ─── DOM References ───────────────────────────────────────────────────────────
const codeInput      = document.getElementById('code-input')      as HTMLTextAreaElement;
const languageSelect = document.getElementById('language-select') as HTMLSelectElement;
const reviewBtn      = document.getElementById('review-btn')      as HTMLButtonElement;
const outputPane     = document.getElementById('output-pane')     as HTMLDivElement;
const outputContent  = document.getElementById('output-content')  as HTMLDivElement;
const charCount      = document.getElementById('char-count')      as HTMLSpanElement;
const clearBtn       = document.getElementById('clear-btn')       as HTMLButtonElement;
const copyBtn        = document.getElementById('copy-btn')        as HTMLButtonElement;
const historyList    = document.getElementById('history-list')    as HTMLDivElement;

let isLoading = false;

// ─── Init ─────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  setupCharCounter();
  setupReviewButton();
  setupClearButton();
  setupCopyButton();
  setupKeyboardShortcut();
  loadHistory();
});

// ─── Character Counter ────────────────────────────────────────────────────────
function setupCharCounter(): void {
  if (!codeInput || !charCount) return;
  codeInput.addEventListener('input', () => {
    const len = codeInput.value.length;
    charCount.textContent = `${len.toLocaleString()} / 50,000`;
    charCount.style.color = len > 45000 ? '#ef4444' : '#94a3b8';
  });
}

// ─── Review Button ────────────────────────────────────────────────────────────
function setupReviewButton(): void {
  if (!reviewBtn) return;
  reviewBtn.addEventListener('click', submitReview);
}

async function submitReview(): Promise<void> {
  if (isLoading) return;
  const code     = codeInput?.value?.trim();
  const language = languageSelect?.value ?? 'javascript';

  if (!code) {
    AppUtils.showToast('Please paste some code before reviewing.', { type: 'error' });
    return;
  }
  if (code.length > 50000) {
    AppUtils.showToast('Code exceeds 50,000 character limit.', { type: 'error' });
    return;
  }

  setLoading(true);
  showSkeletonLoader();

  try {
    const res = await AppUtils.apiPost('/api/review.php', {
      code,
      language,
      csrf_token: AppUtils.getCsrfToken(),
    }, false); // CSRF already injected above

    if (res.success && res.feedback) {
      renderMarkdown(res.feedback as string);
      AppUtils.showToast('Code review complete!', { type: 'success' });
      await loadHistory(); // Refresh history sidebar
    } else {
      showError(res.error ?? 'Review failed. Please try again.');
    }
  } catch (err: unknown) {
    showError(err instanceof Error ? err.message : 'Network error. Please try again.');
  } finally {
    setLoading(false);
  }
}

// ─── Render Markdown ──────────────────────────────────────────────────────────
function renderMarkdown(markdown: string): void {
  if (!outputContent) return;
  outputContent.innerHTML = marked.parse(markdown);
  outputContent.className = 'markdown-output fade-in-up';

  // Syntax highlight code blocks
  if (typeof hljs !== 'undefined') {
    outputContent.querySelectorAll('pre code').forEach(block => {
      hljs.highlightAll();
    });
  }

  outputPane?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  copyBtn && (copyBtn.style.display = 'inline-flex');
}

// ─── Skeleton Loader ──────────────────────────────────────────────────────────
function showSkeletonLoader(): void {
  if (!outputContent) return;
  outputContent.innerHTML = `
    <div class="space-y-3 animate-pulse">
      ${[80, 60, 90, 50, 70, 40, 85, 55].map(w => `
        <div style="height:12px;background:rgba(99,102,241,0.15);border-radius:6px;width:${w}%"></div>
      `).join('')}
      <div style="height:80px;background:rgba(99,102,241,0.08);border-radius:8px;margin-top:8px"></div>
      ${[65, 75, 45].map(w => `
        <div style="height:12px;background:rgba(99,102,241,0.15);border-radius:6px;width:${w}%"></div>
      `).join('')}
    </div>
  `;
}

function showError(message: string): void {
  if (!outputContent) return;
  outputContent.innerHTML = `
    <div class="alert alert-error">
      <span>⚠️</span>
      <span>${message}</span>
    </div>
  `;
}

// ─── Loading State ────────────────────────────────────────────────────────────
function setLoading(state: boolean): void {
  isLoading = state;
  if (!reviewBtn) return;
  if (state) {
    reviewBtn.disabled = true;
    reviewBtn.innerHTML = `<span class="spinner"></span><span>Analyzing...</span>`;
  } else {
    reviewBtn.disabled = false;
    reviewBtn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg><span>Review Code</span>`;
  }
}

// ─── Clear Button ─────────────────────────────────────────────────────────────
function setupClearButton(): void {
  clearBtn?.addEventListener('click', () => {
    if (codeInput) codeInput.value = '';
    if (outputContent) outputContent.innerHTML = getPlaceholderHTML();
    if (charCount) charCount.textContent = '0 / 50,000';
    copyBtn && (copyBtn.style.display = 'none');
  });
}

// ─── Copy Button ─────────────────────────────────────────────────────────────
function setupCopyButton(): void {
  copyBtn?.addEventListener('click', async () => {
    const text = outputContent?.innerText ?? '';
    try {
      await navigator.clipboard.writeText(text);
      AppUtils.showToast('Feedback copied to clipboard!', { type: 'success' });
    } catch (_) {
      AppUtils.showToast('Copy failed. Please select text manually.', { type: 'error' });
    }
  });
}

// ─── Keyboard Shortcut ────────────────────────────────────────────────────────
function setupKeyboardShortcut(): void {
  document.addEventListener('keydown', (e: KeyboardEvent) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
      e.preventDefault();
      submitReview();
    }
  });
}

// ─── Load History ─────────────────────────────────────────────────────────────
async function loadHistory(): Promise<void> {
  // Placeholder – would fetch from /api/history.php
  // Left as extension point
}

// ─── Placeholder HTML ─────────────────────────────────────────────────────────
function getPlaceholderHTML(): string {
  return `
    <div class="flex flex-col items-center justify-center h-full text-center" style="min-height:300px;color:#94a3b8;">
      <div style="font-size:3rem;margin-bottom:1rem;">🤖</div>
      <p style="font-size:1rem;font-weight:600;color:#cbd5e1;margin-bottom:0.5rem;">AI Review Output</p>
      <p style="font-size:0.85rem;">Paste your code on the left and click<br><strong style="color:#6366f1;">Review Code</strong> to get started.</p>
      <p style="font-size:0.78rem;margin-top:1rem;color:#475569;">Shortcut: <kbd style="background:#1e293b;border:1px solid #475569;border-radius:4px;padding:1px 6px;font-size:0.75rem;">Ctrl</kbd> + <kbd style="background:#1e293b;border:1px solid #475569;border-radius:4px;padding:1px 6px;font-size:0.75rem;">Enter</kbd></p>
    </div>
  `;
}
