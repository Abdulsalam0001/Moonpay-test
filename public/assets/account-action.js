(() => {
  'use strict';

  const button = document.querySelector('.copy-address');
  const status = document.getElementById('copy-status');

  if (!button || !status) return;

  button.addEventListener('click', async () => {
    const target = document.getElementById(button.dataset.copyTarget);
    if (!target) return;

    const address = target.textContent.trim();

    try {
      await navigator.clipboard.writeText(address);
      button.querySelector('span').textContent = 'Copied';
      status.textContent = 'Address copied to clipboard.';
    } catch {
      const range = document.createRange();
      range.selectNodeContents(target);
      const selection = window.getSelection();
      selection.removeAllRanges();
      selection.addRange(range);
      status.textContent = 'Address selected. Copy it from your device.';
    }

    window.setTimeout(() => {
      const label = button.querySelector('span');
      if (label) label.textContent = 'Copy address';
      status.textContent = '';
    }, 2200);
  });
})();