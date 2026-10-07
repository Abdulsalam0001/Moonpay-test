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

  function showStep(from, to) {
    from.classList.add('login-leaving');

    window.setTimeout(() => {
      from.hidden = true;
      from.classList.remove('login-leaving');
      to.hidden = false;
      to.classList.remove('login-entering');
      void to.offsetWidth;
      to.classList.add('login-entering');
    }, 160);
  }

  continueBtn.addEventListener('click', () => {
    if (!email.checkValidity()) {
      email.reportValidity();
      return;
    }

    const value = email.value.trim().toLowerCase();
    email.value = value;
    display.textContent = value;
    password.required = true;

    showStep(emailStep, passwordStep);
    window.setTimeout(() => password.focus(), 190);
  });

  changeEmail.addEventListener('click', (event) => {
    event.preventDefault();
    password.required = false;
    showStep(passwordStep, emailStep);
    window.setTimeout(() => email.focus(), 190);
  });

  form.addEventListener('submit', () => {
    signInBtn.disabled = true;
    signInBtn.textContent = 'Loading…';
  });
})();
