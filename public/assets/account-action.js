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
  const sendAsset = document.getElementById('send-asset');
  const sendAmount = document.getElementById('send-amount');
  const sendSymbol = document.getElementById('send-symbol');
  const sendAvailable = document.getElementById('send-available');

  function syncSendAsset() {
    if (!sendAsset || !sendAmount || !sendSymbol || !sendAvailable) return true;
    const option = sendAsset.options[sendAsset.selectedIndex];
    const balance = Number(option?.dataset.balance || 0);
    const symbol = option?.value || '';
    sendSymbol.textContent = symbol;
    sendAmount.max = String(balance);
    sendAvailable.innerHTML = '<strong>' + balance.toFixed(8).replace(/0+$/, '').replace(/\\.$/, '') + ' ' + symbol + '</strong> available';
    if (Number(sendAmount.value) > balance) sendAmount.value = '';
    return balance > 0;
  }

  sendAsset?.addEventListener('change', syncSendAsset);
  sendAmount?.addEventListener('input', () => {
    const option = sendAsset?.options[sendAsset.selectedIndex];
    const balance = Number(option?.dataset.balance || 0);
    if (Number(sendAmount.value) > balance) sendAmount.setCustomValidity('Amount exceeds your available ' + (option?.value || 'token') + ' balance.');
    else sendAmount.setCustomValidity('');
  });
  syncSendAsset();

  function setupLockForm(formId, modalId, closeId) {
    const form = document.getElementById(formId);
    const modal = document.getElementById(modalId);
    const close = document.getElementById(closeId);
    if (!form || !modal) return;

    form.addEventListener('submit', (event) => {
      event.preventDefault();

      if (formId === 'send-form' && !syncSendAsset()) return;

      const amount = form.querySelector('input[name="amount"]');
      if (amount && !amount.checkValidity()) {
        amount.reportValidity();
        return;
      }

      modal.hidden = false;
      document.body.style.overflow = 'hidden';
    });

    const closeModal = () => {
      modal.hidden = true;
      document.body.style.overflow = '';
    };

    close?.addEventListener('click', closeModal);
    modal.addEventListener('click', (event) => {
      if (event.target === modal) closeModal();
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && !modal.hidden) closeModal();
    });
  }

  setupLockForm('send-form', 'send-lock-modal', 'close-send-lock');
  setupLockForm('buy-form', 'buy-lock-modal', 'close-buy-lock');
})();