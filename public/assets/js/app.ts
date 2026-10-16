/**
 * app.ts – Main TypeScript entry point
 * Handles: Auth state, CSRF management, theme, toast notifications
 * Compiled to: public/assets/js/app.js
 */

// ─── Types ────────────────────────────────────────────────────────────────────
interface ApiResponse<T = unknown> {
  success: boolean;
  error?: string;
  message?: string;
  data?: T;
  [key: string]: unknown;
}

interface ToastOptions {
  type: 'success' | 'error' | 'info';
  duration?: number;
}

// ─── State ────────────────────────────────────────────────────────────────────
let csrfToken: string = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '';
let isAuthenticated: boolean = false;

// ─── App Bootstrap ────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', async (): Promise<void> => {
  await bootstrapAuth();
  setupNavToggle();
  setupLogoutButton();
});

// ─── Auth Bootstrap ───────────────────────────────────────────────────────────
async function bootstrapAuth(): Promise<void> {
  try {
    const res  = await apiPost<{ logged_in: boolean; csrf_token: string; username: string }>(
      '/api/auth.php',
      { action: 'status' },
      false // skip CSRF for this call
    );
    isAuthenticated   = res.logged_in ?? false;
    if (res.csrf_token) {
      csrfToken = res.csrf_token;
      updateMetaCsrf(csrfToken);
    }
    updateAuthUI(isAuthenticated, res.username ?? null);
  } catch (_) {
    // Silently fail – user not logged in
  }
}

function updateMetaCsrf(token: string): void {
  let meta = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null;
  if (!meta) {
    meta = document.createElement('meta');
    meta.name = 'csrf-token';
    document.head.appendChild(meta);
  }
  meta.content = token;
}

function updateAuthUI(loggedIn: boolean, username: string | null): void {
  const authSection   = document.getElementById('auth-section');
  const userSection   = document.getElementById('user-section');
  const usernameEl    = document.getElementById('nav-username');
  if (authSection)   authSection.style.display  = loggedIn ? 'none'  : 'flex';
  if (userSection)   userSection.style.display  = loggedIn ? 'flex'  : 'none';
  if (usernameEl && username) usernameEl.textContent = username;
}

// ─── API Helper ───────────────────────────────────────────────────────────────
async function apiPost<T>(
  endpoint: string,
  body: Record<string, unknown>,
  withCsrf: boolean = true
): Promise<T & ApiResponse> {
  if (withCsrf) body.csrf_token = csrfToken;

  const res = await fetch(endpoint, {
    method:  'POST',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify(body),
  });

  const data = await res.json() as T & ApiResponse;

  if (!res.ok && !data.success) {
    throw new Error(data.error ?? `HTTP ${res.status}`);
  }
  return data;
}

// ─── Toast Notifications ──────────────────────────────────────────────────────
function showToast(message: string, options: ToastOptions = { type: 'info' }): void {
  const container = getOrCreateToastContainer();
  const toast     = document.createElement('div');
  const icons     = { success: '✅', error: '❌', info: 'ℹ️' };
  const colors    = {
    success: 'rgba(16,185,129,0.15)',
    error:   'rgba(239,68,68,0.15)',
    info:    'rgba(99,102,241,0.15)',
  };

  toast.style.cssText = `
    background: ${colors[options.type]};
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 0.625rem;
    padding: 0.75rem 1rem;
    color: #f1f5f9;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    backdrop-filter: blur(12px);
    animation: fadeInUp 0.3s ease forwards;
    max-width: 340px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.4);
  `;
  toast.innerHTML = `<span>${icons[options.type]}</span><span>${escapeHtml(message)}</span>`;
  container.appendChild(toast);

  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(10px)';
    toast.style.transition = 'opacity 0.3s, transform 0.3s';
    setTimeout(() => toast.remove(), 300);
  }, options.duration ?? 4000);
}

function getOrCreateToastContainer(): HTMLElement {
  let el = document.getElementById('toast-container');
  if (!el) {
    el = document.createElement('div');
    el.id = 'toast-container';
    el.style.cssText = 'position:fixed;bottom:1.5rem;right:1.5rem;display:flex;flex-direction:column;gap:0.5rem;z-index:9999;';
    document.body.appendChild(el);
  }
  return el;
}

// ─── Navigation Toggle (Mobile) ───────────────────────────────────────────────
function setupNavToggle(): void {
  const btn   = document.getElementById('nav-toggle');
  const menu  = document.getElementById('nav-menu');
  if (!btn || !menu) return;
  btn.addEventListener('click', () => menu.classList.toggle('hidden'));
}

// ─── Logout ───────────────────────────────────────────────────────────────────
function setupLogoutButton(): void {
  document.querySelectorAll('[data-action="logout"]').forEach(el => {
    el.addEventListener('click', async () => {
      try {
        const res = await apiPost('/api/auth.php', { action: 'logout' });
        if (res.success && res.redirect) {
          window.location.href = res.redirect as string;
        }
      } catch (e) {
        showToast('Logout failed. Please try again.', { type: 'error' });
      }
    });
  });
}

// ─── Utility: HTML Escape ─────────────────────────────────────────────────────
function escapeHtml(str: string): string {
  return str
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

// ─── Exports (for use in page-specific TS modules) ────────────────────────────
(window as unknown as Record<string, unknown>).AppUtils = {
  apiPost,
  showToast,
  escapeHtml,
  getCsrfToken: () => csrfToken,
};
