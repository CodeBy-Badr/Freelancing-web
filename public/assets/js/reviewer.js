/**
 * reviewer.js — Compiled from reviewer.ts
 */

const AppUtils = window.AppUtils;
let isLoading = false;

const codeInput      = document.getElementById('code-input');
const languageSelect = document.getElementById('language-select');
const reviewBtn      = document.getElementById('review-btn');
const outputPane     = document.getElementById('output-pane');
const outputContent  = document.getElementById('output-content');
const charCount      = document.getElementById('char-count');
const clearBtn       = document.getElementById('clear-btn');
const copyBtn        = document.getElementById('copy-btn');

document.addEventListener('DOMContentLoaded', () => {
  setupCharCounter();
  setupReviewButton();
  setupClearButton();
  setupCopyButton();
  setupKeyboardShortcut();
});

function setupCharCounter() {
  if (!codeInput || !charCount) return;
  codeInput.addEventListener('input', () => {
    const len = codeInput.value.length;
    charCount.textContent = `${len.toLocaleString()} / 50,000`;
    charCount.style.color = len > 45000 ? '#ef4444' : '#94a3b8';
  });
}

function setupReviewButton() {
  reviewBtn?.addEventListener('click', submitReview);
}

async function submitReview() {
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
    }, false);

    if (res.success && res.feedback) {
      renderMarkdown(res.feedback);
      AppUtils.showToast('Code review complete!', { type: 'success' });
    } else {
      showError(res.error ?? 'Review failed. Please try again.');
    }
  } catch (err) {
    showError(err.message || 'Network error. Please try again.');
  } finally {
    setLoading(false);
  }
}

function renderMarkdown(markdown) {
  if (!outputContent) return;
  outputContent.innerHTML = marked.parse(markdown);
  outputContent.className = 'markdown-output fade-in-up';
  document.querySelectorAll('pre code').forEach(block => hljs?.highlightElement?.(block));
  outputPane?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  if (copyBtn) copyBtn.style.display = 'inline-flex';
}

function showSkeletonLoader() {
  if (!outputContent) return;
  outputContent.innerHTML = `
    <div style="display:flex;flex-direction:column;gap:12px;">
      ${[80,60,90,50,70,40,85,55].map(w => `
        <div style="height:12px;background:rgba(99,102,241,0.15);border-radius:6px;width:${w}%;animation:pulse 1.5s infinite;"></div>
      `).join('')}
      <div style="height:80px;background:rgba(99,102,241,0.08);border-radius:8px;margin-top:8px;animation:pulse 1.5s infinite;"></div>
    </div>
  `;
}

function showError(message) {
  if (!outputContent) return;
  outputContent.innerHTML = `<div class="alert alert-error"><span>⚠️</span><span>${AppUtils.escapeHtml(message)}</span></div>`;
}

function setLoading(state) {
  isLoading = state;
  if (!reviewBtn) return;
  reviewBtn.disabled = state;
  reviewBtn.innerHTML = state
    ? `<span class="spinner"></span><span>Analyzing…</span>`
    : `<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg><span>Review Code</span>`;
}

function setupClearButton() {
  clearBtn?.addEventListener('click', () => {
    if (codeInput) codeInput.value = '';
    if (charCount) charCount.textContent = '0 / 50,000';
    if (outputContent) outputContent.innerHTML = getPlaceholderHTML();
    if (copyBtn) copyBtn.style.display = 'none';
  });
}

function setupCopyButton() {
  copyBtn?.addEventListener('click', async () => {
    try {
      await navigator.clipboard.writeText(outputContent?.innerText ?? '');
      AppUtils.showToast('Feedback copied to clipboard!', { type: 'success' });
    } catch (_) {
      AppUtils.showToast('Copy failed.', { type: 'error' });
    }
  });
}

function setupKeyboardShortcut() {
  document.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
      e.preventDefault();
      submitReview();
    }
  });
}

function getPlaceholderHTML() {
  return `
    <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;min-height:300px;text-align:center;color:#94a3b8;">
      <div style="font-size:3rem;margin-bottom:1rem;">🤖</div>
      <p style="font-size:1rem;font-weight:600;color:#cbd5e1;margin-bottom:0.5rem;">AI Review Output</p>
      <p style="font-size:0.85rem;">Paste your code and click <strong style="color:#6366f1;">Review Code</strong>.</p>
    </div>
  `;
}
