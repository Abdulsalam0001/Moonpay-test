(() => {
  'use strict';

  const balance = document.getElementById('available-balance');
  const btcEquivalent = document.getElementById('balance-btc-equivalent');
  const btcMarketPrice = document.getElementById('balance-market-price');
  if (!balance) return;

  let holding = Number(balance.dataset.btcHolding || 0);
  const fallbackUsd = Number(balance.dataset.fallbackUsd || 0);
  let lastBtcPrice = 0;
  let stopped = false;
  let refreshTimer;

  const money = value => new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 0,
    maximumFractionDigits: 2
  }).format(value);

  const coinPrice = value => {
    if (!Number.isFinite(value)) return '—';
    const digits = value >= 1000 ? 2 : value >= 1 ? 2 : value >= 0.01 ? 4 : 6;
    return '$' + value.toLocaleString('en-US', {
      minimumFractionDigits: digits,
      maximumFractionDigits: digits
    });
  };

  const keyFor = symbol => String(symbol || '').toLowerCase().replace(/[^a-z0-9]+/g, '-');

  // Show real token logos when available; gracefully fall back to a symbol initial.
  document.querySelectorAll('.crypto-asset-icon').forEach(icon => {
    const img = icon.querySelector('.crypto-token-icon');
    const fallback = icon.querySelector('.crypto-token-fallback');
    if (!img || !fallback) return;
    const showFallback = () => { img.hidden = true; fallback.hidden = false; };
    img.addEventListener('error', showFallback, { once: true });
    img.addEventListener('load', () => { img.hidden = false; fallback.hidden = true; }, { once: true });
    if (img.complete && img.naturalWidth === 0) showFallback();
    else if (img.complete && img.naturalWidth > 0) fallback.hidden = true;
  });

  function renderPrices(prices) {
    if (!prices || typeof prices !== 'object') return;

    Object.entries(prices).forEach(([symbol, data]) => {
      const key = keyFor(symbol);
      const price = Number(data && data.usd);
      if (!Number.isFinite(price) || price <= 0) return;

      document.querySelectorAll('.portfolio-asset').forEach(row => {
        if ((row.dataset.assetSymbol || '').toUpperCase() !== symbol.toUpperCase()) return;
        let amount = Number(row.dataset.assetAmount || 0);
        // If a demo account has a USD balance but no saved BTC amount, estimate
        // the BTC equivalent from the first usable market quote and keep it fixed.
        if (symbol.toUpperCase() === 'BTC' && amount <= 0 && fallbackUsd > 0) {
          amount = fallbackUsd / price;
          row.dataset.assetAmount = String(amount);
          const amountEl = row.querySelector('[id^="asset-amount-"]');
          if (amountEl) amountEl.textContent = amount.toLocaleString('en-US', { maximumFractionDigits: 8 });
        }
        const priceEl = document.getElementById('asset-price-' + key);
        const valueEl = document.getElementById('asset-value-' + key);
        const changeEl = document.getElementById('asset-change-' + key);
        if (priceEl) priceEl.textContent = coinPrice(price) + ' per coin';
        if (valueEl) valueEl.textContent = '≈ ' + money(amount * price) + ' USD';
        if (changeEl && data.change_24h !== null && Number.isFinite(Number(data.change_24h))) {
          const change = Number(data.change_24h);
          changeEl.innerHTML = '';
          const span = document.createElement('span');
          span.className = change >= 0 ? 'positive' : 'negative';
          span.textContent = (change >= 0 ? '+' : '') + change.toFixed(2) + '% today';
          changeEl.appendChild(span);
        }
      });

      if (symbol.toUpperCase() === 'BTC') {
        lastBtcPrice = price;
        if (holding <= 0 && fallbackUsd > 0) holding = fallbackUsd / price;
        if (holding > 0) {
          balance.textContent = money(holding * price);
          if (btcEquivalent) {
            btcEquivalent.textContent = holding.toLocaleString('en-US', {
              minimumFractionDigits: 0,
              maximumFractionDigits: 8
            }) + ' BTC';
          }
        }
        if (btcMarketPrice) btcMarketPrice.textContent = '1 BTC = ' + coinPrice(price);
      }
    });
  }

  async function refreshPrices() {
    if (stopped) return;
    try {
      const response = await fetch('/market-prices.php', {
        method: 'GET',
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json' },
        cache: 'no-store'
      });
      if (!response.ok) return;
      const result = await response.json();
      renderPrices(result.prices);
    } catch (_) {
      // Keep the last rendered prices; the dashboard does not show a connection error.
    } finally {
      if (!stopped) refreshTimer = window.setTimeout(refreshPrices, 30000);
    }
  }

  // Use the server-rendered CoinGecko snapshot immediately, then refresh through our
  // same-origin PHP endpoint. No browser WebSocket connection is required.
  refreshPrices();
  window.addEventListener('pagehide', () => {
    stopped = true;
    window.clearTimeout(refreshTimer);
  }, { once: true });
})();
