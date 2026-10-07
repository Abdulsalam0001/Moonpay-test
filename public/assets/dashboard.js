(() => {
  'use strict';

  const modal = document.getElementById('security-onboarding');
  const slides = [...document.querySelectorAll('.onboarding-slide')];
  const dots = [...document.querySelectorAll('.onboarding-dots span')];
  const next = document.getElementById('onboarding-next');
  const complete = document.getElementById('onboarding-complete');

  if (!modal || !slides.length || !next || !complete) return;

  let index = 0;

  function render() {
    slides.forEach((slide, i) => slide.classList.toggle('is-active', i === index));
    dots.forEach((dot, i) => dot.classList.toggle('is-active', i === index));
    const count = document.getElementById('onboarding-count');
    if (count) count.textContent = (index + 1) + ' / ' + slides.length;

    if (index === slides.length - 1) {
      next.style.display = 'none';
      complete.style.display = 'block';
    } else {
      next.style.display = 'block';
      complete.style.display = 'none';
    }
  }

  next.addEventListener('click', () => {
    if (index < slides.length - 1) {
      index += 1;
      render();
    }
  });

  render();
})();