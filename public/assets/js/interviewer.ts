/**
 * interviewer.ts – Mock Interviewer page logic
 * Handles chat UI, session management, role selection, and scoring display.
 */

declare const marked: { parse: (md: string) => string };

const AppUtils = (window as unknown as Record<string, { apiPost: Function; showToast: Function; getCsrfToken: Function }>).AppUtils;

// ─── DOM Refs ─────────────────────────────────────────────────────────────────
const chatWindow     = document.getElementById('chat-window')     as HTMLDivElement;
const chatInput      = document.getElementById('chat-input')      as HTMLTextAreaElement;
const sendBtn        = document.getElementById('send-btn')        as HTMLButtonElement;
const startBtn       = document.getElementById('start-btn')       as HTMLButtonElement;
const endBtn         = document.getElementById('end-btn')         as HTMLButtonElement;
const roleSelect     = document.getElementById('role-select')     as HTMLSelectElement;
const sessionStatus  = document.getElementById('session-status')  as HTMLDivElement;
const scoreDisplay   = document.getElementById('score-display')   as HTMLDivElement;

// ─── State ────────────────────────────────────────────────────────────────────
let sessionActive: boolean  = false;
let messageCount: number    = 0;
const MAX_MESSAGES: number  = 12; // 6 Q&A rounds

// ─── Init ─────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  startBtn?.addEventListener('click', startInterview);
  endBtn?.addEventListener('click',   endInterview);
  sendBtn?.addEventListener('click',  sendMessage);

  // Enter to send (Shift+Enter for newline)
  chatInput?.addEventListener('keydown', (e: KeyboardEvent) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      sendMessage();
    }
  });

  setInputState(false);
});

// ─── Start Interview ──────────────────────────────────────────────────────────
async function startInterview(): Promise<void> {
  const role = roleSelect?.value;
  if (!role) {
    AppUtils.showToast('Please select a developer role.', { type: 'error' });
    return;
  }

  clearChat();
  setSessionStatus('🟡 Starting session…');

  try {
    const res = await AppUtils.apiPost('/api/chat.php', {
      action: 'start',
      role,
      csrf_token: AppUtils.getCsrfToken(),
    }, false);

    if (res.success) {
      sessionActive = true;
      messageCount  = 0;
      appendAIMessage(res.message as string);
      setInputState(true);
      setSessionStatus(`🟢 Interview in progress — ${role}`);
      startBtn?.classList.add('hidden');
      endBtn?.classList.remove('hidden');
      roleSelect && (roleSelect.disabled = true);
      AppUtils.showToast('Interview started! Good luck 🚀', { type: 'success' });
    } else {
      setSessionStatus('🔴 Failed to start');
      AppUtils.showToast(res.error ?? 'Failed to start interview.', { type: 'error' });
    }
  } catch (err: unknown) {
    setSessionStatus('🔴 Error');
    AppUtils.showToast(err instanceof Error ? err.message : 'Network error.', { type: 'error' });
  }
}

// ─── Send Message ─────────────────────────────────────────────────────────────
async function sendMessage(): Promise<void> {
  if (!sessionActive) return;

  const message = chatInput?.value?.trim();
  if (!message) return;

  appendUserMessage(message);
  chatInput!.value = '';
  setInputState(false);
  showTypingIndicator();
  messageCount++;

  try {
    const res = await AppUtils.apiPost('/api/chat.php', {
      action: 'message',
      message,
      csrf_token: AppUtils.getCsrfToken(),
    }, false);

    removeTypingIndicator();

    if (res.success) {
      appendAIMessage(res.message as string);

      // Auto-end after MAX_MESSAGES
      if (messageCount >= MAX_MESSAGES) {
        await endInterview();
      } else {
        setInputState(true);
      }
    } else {
      appendSystemMessage(`⚠️ ${res.error ?? 'An error occurred.'}`);
      setInputState(true);
    }
  } catch (err: unknown) {
    removeTypingIndicator();
    appendSystemMessage(`⚠️ ${err instanceof Error ? err.message : 'Network error.'}`);
    setInputState(true);
  }
}

// ─── End Interview ────────────────────────────────────────────────────────────
async function endInterview(): Promise<void> {
  if (!sessionActive) return;
  setInputState(false);
  setSessionStatus('🟡 Generating final evaluation…');
  showTypingIndicator();

  try {
    const res = await AppUtils.apiPost('/api/chat.php', {
      action: 'end',
      csrf_token: AppUtils.getCsrfToken(),
    }, false);

    removeTypingIndicator();

    if (res.success) {
      sessionActive = false;
      const finalScore = res.final_score as number | null;
      setSessionStatus('🏁 Interview completed');
      startBtn?.classList.remove('hidden');
      endBtn?.classList.add('hidden');
      roleSelect && (roleSelect.disabled = false);

      if (finalScore !== null && finalScore !== undefined) {
        showScoreWidget(finalScore);
      }

      appendSystemMessage('✅ Interview session ended. Review your transcript above!');
      AppUtils.showToast('Interview complete! Check your score.', { type: 'success' });
    }
  } catch (err: unknown) {
    removeTypingIndicator();
    AppUtils.showToast('Error ending session.', { type: 'error' });
    setInputState(true);
  }
}

// ─── Chat Rendering ───────────────────────────────────────────────────────────
function appendUserMessage(text: string): void {
  const wrapper = document.createElement('div');
  wrapper.className = 'flex justify-end mb-3';
  wrapper.innerHTML = `
    <div class="chat-bubble-user fade-in-up">
      ${escapeHtml(text)}
    </div>
  `;
  chatWindow?.appendChild(wrapper);
  scrollToBottom();
}

function appendAIMessage(markdown: string): void {
  const wrapper = document.createElement('div');
  wrapper.className = 'flex gap-3 mb-3 items-start';
  wrapper.innerHTML = `
    <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:14px;">🤖</div>
    <div class="chat-bubble-ai markdown-output fade-in-up">${marked.parse(markdown)}</div>
  `;
  chatWindow?.appendChild(wrapper);
  scrollToBottom();
}

function appendSystemMessage(text: string): void {
  const el = document.createElement('div');
  el.className = 'text-center my-3';
  el.style.cssText = 'font-size:0.8rem;color:#94a3b8;';
  el.textContent = text;
  chatWindow?.appendChild(el);
  scrollToBottom();
}

function showTypingIndicator(): void {
  const el = document.createElement('div');
  el.id = 'typing-indicator';
  el.className = 'flex gap-3 mb-3 items-start';
  el.innerHTML = `
    <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:14px;">🤖</div>
    <div class="chat-bubble-ai">
      <div class="typing-indicator">
        <span></span><span></span><span></span>
      </div>
    </div>
  `;
  chatWindow?.appendChild(el);
  scrollToBottom();
}

function removeTypingIndicator(): void {
  document.getElementById('typing-indicator')?.remove();
}

function clearChat(): void {
  if (chatWindow) chatWindow.innerHTML = '';
}

function scrollToBottom(): void {
  if (chatWindow) chatWindow.scrollTop = chatWindow.scrollHeight;
}

// ─── Score Widget ─────────────────────────────────────────────────────────────
function showScoreWidget(score: number): void {
  if (!scoreDisplay) return;
  const level = score >= 7 ? 'high' : score >= 4 ? 'medium' : 'low';
  const label = score >= 7 ? '🏆 Great job!' : score >= 4 ? '📈 Keep improving!' : '📚 Study more!';

  scoreDisplay.innerHTML = `
    <div class="glass-card p-5 text-center fade-in-up">
      <div class="score-badge ${level} mx-auto mb-3" style="width:64px;height:64px;font-size:1.25rem;">
        ${score.toFixed(1)}
      </div>
      <p style="font-weight:700;font-size:1rem;color:#f1f5f9;">Final Score: ${score.toFixed(1)}/10</p>
      <p style="font-size:0.85rem;color:#94a3b8;margin-top:0.25rem;">${label}</p>
    </div>
  `;
  scoreDisplay.style.display = 'block';
}

// ─── Helpers ──────────────────────────────────────────────────────────────────
function setInputState(enabled: boolean): void {
  if (chatInput) chatInput.disabled = !enabled;
  if (sendBtn)   sendBtn.disabled   = !enabled;
}

function setSessionStatus(text: string): void {
  if (sessionStatus) sessionStatus.textContent = text;
}

function escapeHtml(str: string): string {
  return str
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}
