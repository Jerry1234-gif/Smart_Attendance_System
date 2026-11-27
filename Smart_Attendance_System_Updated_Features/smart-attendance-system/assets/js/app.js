document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      const message = form.getAttribute('data-confirm') || 'Are you sure?';
      if (!window.confirm(message)) event.preventDefault();
    });
  });

  // Existing admin user form behaviour: show the student ID field only for students.
  const roleSelect = document.getElementById('user-role');
  const studentField = document.getElementById('student-number-field');
  const studentInput = studentField ? studentField.querySelector('input') : null;
  const updateStudentField = () => {
    if (!roleSelect || !studentField) return;
    const isStudent = roleSelect.value === 'student';
    studentField.hidden = !isStudent;
    if (studentInput) studentInput.required = isStudent;
  };
  if (roleSelect) {
    roleSelect.addEventListener('change', updateStudentField);
    updateStudentField();
  }

  // Global accessibility and display preferences. Preferences are stored locally only.
  const root = document.documentElement;
  const storageKey = 'smartAttendanceAccessibilityV1';
  const defaults = {
    textSize: '1', colourTheme: 'green', displayMode: 'light',
    contrast: false, readableFont: false, underlineLinks: false,
    reduceMotion: false, largeCursor: false
  };
  const themes = {
    green:  { primary: '#21845c', dark: '#176847', pale: '#e7f4ed', deep: '#173d32' },
    teal:   { primary: '#168b83', dark: '#0f6963', pale: '#e1f5f2', deep: '#123f3c' },
    blue:   { primary: '#286db4', dark: '#1d5088', pale: '#e6f0fb', deep: '#183653' },
    purple: { primary: '#7652a8', dark: '#563b80', pale: '#f0eafa', deep: '#38294e' },
    orange: { primary: '#bd7130', dark: '#8d501f', pale: '#fbefe3', deep: '#4a3322' }
  };
  let preferences = { ...defaults };
  try {
    const saved = JSON.parse(localStorage.getItem(storageKey) || '{}');
    preferences = { ...defaults, ...saved };
  } catch (_) { /* Ignore unavailable or invalid local storage. */ }

  const persist = () => {
    try { localStorage.setItem(storageKey, JSON.stringify(preferences)); } catch (_) { /* Private browsing may block storage. */ }
  };
  const applyPreferences = () => {
    const theme = themes[preferences.colourTheme] || themes.green;
    root.style.setProperty('--green', theme.primary);
    root.style.setProperty('--green-dark', theme.dark);
    root.style.setProperty('--green-pale', theme.pale);
    root.style.setProperty('--brand-deep', theme.deep);
    root.style.setProperty('--interface-zoom', preferences.textSize || '1');
    root.dataset.mode = preferences.displayMode === 'dark' ? 'dark' : 'light';
    document.body.classList.toggle('a11y-high-contrast', !!preferences.contrast);
    document.body.classList.toggle('a11y-readable-font', !!preferences.readableFont);
    document.body.classList.toggle('a11y-underline-links', !!preferences.underlineLinks);
    document.body.classList.toggle('a11y-reduce-motion', !!preferences.reduceMotion);
    document.body.classList.toggle('a11y-large-cursor', !!preferences.largeCursor);

    const textSize = document.querySelector('[data-text-size]');
    const colourTheme = document.querySelector('[data-colour-theme]');
    const displayMode = document.querySelector('[data-display-mode]');
    if (textSize) textSize.value = preferences.textSize;
    if (colourTheme) colourTheme.value = preferences.colourTheme;
    if (displayMode) displayMode.value = preferences.displayMode;
    document.querySelectorAll('[data-a11y]').forEach((input) => {
      const key = input.dataset.a11y.replace(/-([a-z])/g, (_, c) => c.toUpperCase());
      input.checked = !!preferences[key];
    });
  };
  applyPreferences();

  const panel = document.querySelector('[data-accessibility-panel]');
  const toggle = document.querySelector('[data-accessibility-toggle]');
  const close = document.querySelector('[data-accessibility-close]');
  const setPanelOpen = (open) => {
    if (!panel || !toggle) return;
    panel.hidden = !open;
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
  };
  if (toggle) toggle.addEventListener('click', () => setPanelOpen(panel.hidden));
  if (close) close.addEventListener('click', () => setPanelOpen(false));

  document.querySelector('[data-text-size]')?.addEventListener('change', (event) => {
    preferences.textSize = event.target.value; applyPreferences(); persist();
  });
  document.querySelector('[data-colour-theme]')?.addEventListener('change', (event) => {
    preferences.colourTheme = event.target.value; applyPreferences(); persist();
  });
  document.querySelector('[data-display-mode]')?.addEventListener('change', (event) => {
    preferences.displayMode = event.target.value; applyPreferences(); persist();
  });
  document.querySelectorAll('[data-a11y]').forEach((input) => {
    input.addEventListener('change', () => {
      const key = input.dataset.a11y.replace(/-([a-z])/g, (_, c) => c.toUpperCase());
      preferences[key] = input.checked; applyPreferences(); persist();
    });
  });
  document.querySelector('[data-reset-accessibility]')?.addEventListener('click', () => {
    preferences = { ...defaults }; applyPreferences(); persist();
  });

  // Text-to-speech uses the browser's built-in Web Speech API; no audio file is uploaded.
  const readButton = document.querySelector('[data-speech-read]');
  const stopButton = document.querySelector('[data-speech-stop]');
  const speechSupported = 'speechSynthesis' in window && 'SpeechSynthesisUtterance' in window;
  const stopSpeaking = () => {
    if (speechSupported) window.speechSynthesis.cancel();
    if (readButton) readButton.disabled = false;
    if (stopButton) stopButton.hidden = true;
  };
  if (readButton) {
    readButton.addEventListener('click', () => {
      if (!speechSupported) {
        window.alert('Text-to-speech is not supported by this browser. Try a recent version of Chrome or Edge.');
        return;
      }
      stopSpeaking();
      const main = document.querySelector('#main-content');
      const text = main ? main.innerText.replace(/\s+/g, ' ').trim() : document.body.innerText;
      if (!text) return;
      const utterance = new SpeechSynthesisUtterance(text.slice(0, 12000));
      utterance.rate = 0.95;
      utterance.onend = stopSpeaking;
      utterance.onerror = stopSpeaking;
      readButton.disabled = true;
      if (stopButton) stopButton.hidden = false;
      window.speechSynthesis.speak(utterance);
    });
  }
  stopButton?.addEventListener('click', stopSpeaking);
  window.addEventListener('beforeunload', stopSpeaking);
});
