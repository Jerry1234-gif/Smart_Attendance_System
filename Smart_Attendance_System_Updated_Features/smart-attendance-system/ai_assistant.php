<?php
require_once __DIR__ . '/includes/guard.php';
$user = require_login();
$aiConfig = require __DIR__ . '/config/ai.php';
$aiConnected = trim((string)$aiConfig['api_key']) !== '';
render_header('AI Assistant', 'Ask for help using the Smart Attendance System.');
?>
<section class="content-grid two-col ai-page-grid align-start">
    <div class="panel ai-chat-panel">
        <div class="panel-heading"><div><h2>Smart Attendance Assistant</h2><p><?= $aiConnected ? 'AI connection configured' : 'Guided demo mode — connect an API key for live AI answers' ?></p></div><span class="ai-status-dot <?= $aiConnected ? 'is-connected' : '' ?>" aria-label="<?= $aiConnected ? 'AI connected' : 'Demo mode' ?>"></span></div>
        <div class="ai-messages" id="ai-messages" aria-live="polite" aria-label="Assistant conversation">
            <div class="ai-message ai-message-assistant"><strong>Assistant</strong><p>Hello <?= e(explode(' ', trim($user['full_name']))[0]) ?>! Ask me about QR attendance, student IDs, courses, reports, accessibility or using this website.</p></div>
        </div>
        <form id="ai-chat-form" class="ai-chat-form" data-endpoint="<?= e(app_url('api/ai_chat.php')) ?>">
            <input type="hidden" id="ai-csrf" value="<?= e(csrf_token()) ?>">
            <label class="visually-hidden" for="ai-message">Your question</label>
            <textarea id="ai-message" name="message" rows="2" maxlength="1200" required placeholder="Type your question here..."></textarea>
            <button class="button button-primary" type="submit" id="ai-send">Send question</button>
        </form>
        <p class="tiny-note">Do not enter passwords, private student records or other sensitive information. The assistant gives general guidance and cannot change attendance records.</p>
    </div>
    <aside class="panel">
        <h2>Suggested questions</h2>
        <p class="muted">Choose a question to place it in the message box.</p>
        <div class="ai-suggestions">
            <button type="button" class="ai-suggestion" data-ai-prompt="How does a student scan the lecturer's QR code?">How do I scan a QR code?</button>
            <button type="button" class="ai-suggestion" data-ai-prompt="Why must a Limkokwing student ID start with 90500?">Why must my ID start with 90500?</button>
            <button type="button" class="ai-suggestion" data-ai-prompt="How can an administrator add a new student or lecturer?">How do I add a user?</button>
            <button type="button" class="ai-suggestion" data-ai-prompt="How can I view or export attendance reports?">How do I view attendance reports?</button>
            <button type="button" class="ai-suggestion" data-ai-prompt="How do I increase text size or switch to dark mode?">How do I use accessibility settings?</button>
        </div>
        <div class="alert alert-info ai-privacy-note">The assistant is for general system guidance. For account-specific or attendance-record problems, contact the administrator through Help &amp; Support.</div>
    </aside>
</section>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('ai-chat-form');
  const input = document.getElementById('ai-message');
  const messages = document.getElementById('ai-messages');
  const send = document.getElementById('ai-send');
  const csrf = document.getElementById('ai-csrf').value;
  const addMessage = (role, text, pending = false) => {
    const item = document.createElement('div');
    item.className = 'ai-message ' + (role === 'You' ? 'ai-message-user' : 'ai-message-assistant');
    const title = document.createElement('strong'); title.textContent = role;
    const paragraph = document.createElement('p'); paragraph.textContent = text;
    if (pending) item.dataset.pending = '1';
    item.append(title, paragraph); messages.appendChild(item); messages.scrollTop = messages.scrollHeight;
    return item;
  };
  document.querySelectorAll('[data-ai-prompt]').forEach(button => button.addEventListener('click', () => {
    input.value = button.dataset.aiPrompt; input.focus();
  }));
  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const message = input.value.trim(); if (!message) return;
    addMessage('You', message); input.value = ''; send.disabled = true; send.textContent = 'Sending…';
    const pending = addMessage('Assistant', 'Thinking…', true);
    try {
      const body = new URLSearchParams({ csrf_token: csrf, message });
      const response = await fetch(form.dataset.endpoint, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8', 'Accept': 'application/json' }, body });
      const data = await response.json();
      pending.querySelector('p').textContent = data.reply || 'I could not prepare an answer. Please try again.';
      if (!response.ok) pending.classList.add('ai-message-error');
    } catch (_) {
      pending.querySelector('p').textContent = 'The assistant could not connect. Check your internet connection or contact the administrator.';
      pending.classList.add('ai-message-error');
    } finally { pending.removeAttribute('data-pending'); send.disabled = false; send.textContent = 'Send question'; messages.scrollTop = messages.scrollHeight; }
  });
});
</script>
<?php render_footer(); ?>
