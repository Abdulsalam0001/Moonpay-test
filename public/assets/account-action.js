(() => {
  'use strict';

  const button = document.querySelector('.copy-address');
  const status = document.getElementById('copy-status');

  if (button && status) {
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
  }

  const sendForm = document.getElementById('send-form');
  const lockModal = document.getElementById('send-lock-modal');
  const closeLock = document.getElementById('close-send-lock');

  if (sendForm && lockModal) {
    sendForm.addEventListener('submit', (event) => {
      event.preventDefault();

      const amount = sendForm.querySelector('input[name="amount"]');
      if (!amount || !amount.checkValidity()) {
        amount?.reportValidity();
        return;
      }

      lockModal.hidden = false;
      document.body.style.overflow = 'hidden';
    });
  }

  function closeModal() {
    if (!lockModal) return;
    lockModal.hidden = true;
    document.body.style.overflow = '';
  }

  closeLock?.addEventListener('click', closeModal);

  lockModal?.addEventListener('click', (event) => {
    if (event.target === lockModal) closeModal();
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && lockModal && !lockModal.hidden) {
      closeModal();
    }
  });
})();