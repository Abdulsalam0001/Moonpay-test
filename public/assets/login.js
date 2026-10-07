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

  let animating = false;

  function showStep(from, to, direction = 'forward') {
    if (animating) return;
    animating = true;

    from.dataset.direction = direction;
    from.classList.remove('login-leaving', 'login-leaving-reverse');
    void from.offsetWidth;
    from.classList.add(direction === 'forward' ? 'login-leaving' : 'login-leaving-reverse');

    window.setTimeout(() => {
      from.hidden = true;
      from.classList.remove('login-leaving', 'login-leaving-reverse');

      to.hidden = false;
      to.dataset.direction = direction;
      to.classList.remove('login-entering', 'login-entering-reverse');
      void to.offsetWidth;
      to.classList.add(direction === 'forward' ? 'login-entering' : 'login-entering-reverse');

      window.setTimeout(() => {
        to.classList.remove('login-entering', 'login-entering-reverse');
        animating = false;
      }, 520);
    }, 300);
  }

  continueBtn.addEventListener('click', () => {
    if (animating || !email.checkValidity()) {
      if (!email.checkValidity()) email.reportValidity();
      return;
    }

    const value = email.value.trim().toLowerCase();
    email.value = value;
    display.textContent = value;
    password.required = true;

    // Deliberately pauses before the next screen swipes in from the right.
    showStep(emailStep, passwordStep, 'forward');
    window.setTimeout(() => password.focus(), 820);
  });

  changeEmail.addEventListener('click', (event) => {
    event.preventDefault();
    if (animating) return;

    password.required = false;

    // Reverse direction: the email screen swipes back in from the left.
    showStep(passwordStep, emailStep, 'reverse');
    window.setTimeout(() => email.focus(), 820);
  });

  form.addEventListener('submit', () => {
    signInBtn.disabled = true;
    signInBtn.textContent = 'Loading…';
  });
})();
