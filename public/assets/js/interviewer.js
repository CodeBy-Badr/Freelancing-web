/**
 * interviewer.js — Compiled from interviewer.ts
 */

const AppUtils = window.AppUtils;

const chatWindow    = document.getElementById('chat-window');
const chatInput     = document.getElementById('chat-input');
const sendBtn       = document.getElementById('send-btn');
const startBtn      = document.getElementById('start-btn');
const endBtn        = document.getElementById('end-btn');
const roleSelect    = document.getElementById('role-select');
const sessionStatus = document.getElementById('session-status');
const scoreDisplay  = document.getElementById('score-display');

let sessionActive = false;
let messageCount  = 0;
const MAX_MESSAGES = 12;

document.addEventListener('DOMContentLoaded', () => {
  startBtn?.addEventListener('click', startInterview);
  endBtn?.addEventListener('click',   endInterview);
  sendBtn?.addEventListener('click',  sendMessage);

  chatInput?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      sendMessage();
    }
  });

  setInputState(false);
});

async function startInterview() {
  const role = roleSelect?.value;
  if (!role) { AppUtils.showToast('Please select a developer role.', { type: 'error' }); return; }

  clearChat();
  setSessionStatus('🟡 Starting session…');

  try {
    const res = await AppUtils.apiPost('/api/chat.php', {
      action: 'start', role, csrf_token: AppUtils.getCsrfToken()
    }, false);

    if (res.success) {
      sessionActive = true;
      messageCount  = 0;
      appendAIMessage(res.message);
      setInputState(true);
      setSessionStatus(`🟢 Interview in progress — ${role}`);
      startBtn?.classList.add('hidden');
      endBtn?.classList.remove('hidden');
      if (roleSelect) roleSelect.disabled = true;
      AppUtils.showToast('Interview started! Good luck 🚀', { type: 'success' });
    } else {
      setSessionStatus('🔴 Failed to start');
      AppUtils.showToast(res.error ?? 'Failed to start interview.', { type: 'error' });
    }
  } catch (err) {
    setSessionStatus('🔴 Error');
    AppUtils.showToast(err.message || 'Network error.', { type: 'error' });
  }
}

async function sendMessage() {
  if (!sessionActive) return;
  const message = chatInput?.value?.trim();
  if (!message) return;

  appendUserMessage(message);
  chatInput.value = '';
  setInputState(false);
  showTypingIndicator();
  messageCount++;

  try {
    const res = await AppUtils.apiPost('/api/chat.php', {
      action: 'message', message, csrf_token: AppUtils.getCsrfToken()
    }, false);

    removeTypingIndicator();

    if (res.success) {
      appendAIMessage(res.message);
      if (messageCount >= MAX_MESSAGES) {
        await endInterview();
      } else {
        setInputState(true);
      }
    } else {
      appendSystemMessage(`⚠️ ${res.error ?? 'An error occurred.'}`);
      setInputState(true);
    }
  } catch (err) {
    removeTypingIndicator();
    appendSystemMessage(`⚠️ ${err.message || 'Network error.'}`);
    setInputState(true);
  }
}

async function endInterview() {
  if (!sessionActive) return;
  setInputState(false);
  setSessionStatus('🟡 Generating final evaluation…');
  showTypingIndicator();

  try {
    const res = await AppUtils.apiPost('/api/chat.php', {
      action: 'end', csrf_token: AppUtils.getCsrfToken()
    }, false);

    removeTypingIndicator();

    if (res.success) {
      sessionActive = false;
      setSessionStatus('🏁 Interview completed');
      startBtn?.classList.remove('hidden');
      endBtn?.classList.add('hidden');
      if (roleSelect) roleSelect.disabled = false;
      if (res.final_score !== null && res.final_score !== undefined) {
        showScoreWidget(res.final_score);
      }
      appendSystemMessage('✅ Interview session ended. Review your transcript above!');
      AppUtils.showToast('Interview complete! Check your score.', { type: 'success' });
    }
  } catch (err) {
    removeTypingIndicator();
    AppUtils.showToast('Error ending session.', { type: 'error' });
    setInputState(true);
  }
}

// ─── Chat Rendering ───────────────────────────────────────────────────────────
function appendUserMessage(text) {
  const wrapper = document.createElement('div');
  wrapper.className = 'flex justify-end mb-3';
  wrapper.innerHTML = `<div class="chat-bubble-user fade-in-up">${AppUtils.escapeHtml(text)}</div>`;
  chatWindow?.appendChild(wrapper);
  scrollToBottom();
}

function appendAIMessage(markdown) {
  const wrapper = document.createElement('div');
  wrapper.className = 'flex gap-3 mb-3 items-start';
  wrapper.innerHTML = `
    <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:14px;">🤖</div>
    <div class="chat-bubble-ai markdown-output fade-in-up">${marked.parse(markdown)}</div>
  `;
  chatWindow?.appendChild(wrapper);
  scrollToBottom();
}

function appendSystemMessage(text) {
  const el = document.createElement('div');
  el.style.cssText = 'text-align:center;font-size:0.8rem;color:#94a3b8;margin:0.75rem 0;';
  el.textContent = text;
  chatWindow?.appendChild(el);
  scrollToBottom();
}

function showTypingIndicator() {
  const el = document.createElement('div');
  el.id = 'typing-indicator';
  el.className = 'flex gap-3 mb-3 items-start';
  el.innerHTML = `
    <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:14px;">🤖</div>
    <div class="chat-bubble-ai"><div class="typing-indicator"><span></span><span></span><span></span></div></div>
  `;
  chatWindow?.appendChild(el);
  scrollToBottom();
}

function removeTypingIndicator() {
  document.getElementById('typing-indicator')?.remove();
}

function clearChat() {
  if (chatWindow) chatWindow.innerHTML = '';
}

function scrollToBottom() {
  if (chatWindow) chatWindow.scrollTop = chatWindow.scrollHeight;
}

function showScoreWidget(score) {
  if (!scoreDisplay) return;
  const level = score >= 7 ? 'high' : score >= 4 ? 'medium' : 'low';
  const label = score >= 7 ? '🏆 Great job!' : score >= 4 ? '📈 Keep improving!' : '📚 Study more!';
  scoreDisplay.innerHTML = `
    <div class="glass-card p-5 text-center fade-in-up">
      <div class="score-badge ${level} mx-auto mb-3" style="width:64px;height:64px;font-size:1.25rem;">
        ${parseFloat(score).toFixed(1)}
      </div>
      <p style="font-weight:700;font-size:1rem;color:#f1f5f9;">Final Score: ${parseFloat(score).toFixed(1)}/10</p>
      <p style="font-size:0.85rem;color:#94a3b8;margin-top:4px;">${label}</p>
    </div>
  `;
  scoreDisplay.style.display = 'block';
}

function setInputState(enabled) {
  if (chatInput) chatInput.disabled = !enabled;
  if (sendBtn)   sendBtn.disabled   = !enabled;
}

function setSessionStatus(text) {
  if (sessionStatus) sessionStatus.textContent = text;
}
