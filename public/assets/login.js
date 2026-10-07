(() => {
  'use strict';

  const form = document.getElementById('login-form');
  const emailStep = document.getElementById('email-step');
  const passwordStep = document.getElementById('password-step');
  const email = document.getElementById('email');
  const display = document.getElementById('email-display');
  const continueBtn = document.getElementById('continue-btn');
  const password = document.getElementById('password');
  const changeEmail = document.getElementById('change-email');
  const signInBtn = document.getElementById('signin-btn');

  if (!form || !emailStep || !passwordStep || !email || !display || !continueBtn || !password || !changeEmail || !signInBtn) {
    return;
  }

  continueBtn.addEventListener('click', () => {
    const value = email.value.trim().toLowerCase();

    if (!email.checkValidity()) {
      email.reportValidity();
      return;
    }

    display.textContent = value;
    emailStep.hidden = true;
    passwordStep.hidden = false;
    password.required = true;
    password.focus();
  });

  changeEmail.addEventListener('click', (event) => {
    event.preventDefault();
    passwordStep.hidden = true;
    emailStep.hidden = false;
    password.required = false;
    password.focus();
  });

  form.addEventListener('submit', () => {
    signInBtn.disabled = true;
    signInBtn.textContent = 'Loading…';
  });
})();
