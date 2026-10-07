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
    slides.forEach((slide, i) => {
      const active = i === index;
      slide.classList.toggle('is-active', active);
      slide.hidden = !active;
      slide.setAttribute('aria-hidden', active ? 'false' : 'true');
    });
    dots.forEach((dot, i) => dot.classList.toggle('is-active', i === index));
    const count = document.getElementById('onboarding-count');
    if (count) count.textContent = (index + 1) + ' / ' + slides.length;
    const last = index === slides.length - 1;
    next.hidden = last;
    complete.style.display = last ? 'block' : 'none';
  }
  next.addEventListener('click', () => {
    if (index < slides.length - 1) { index += 1; render(); }
  });
  render();
})();