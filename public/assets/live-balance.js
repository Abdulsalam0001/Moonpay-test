(() => {
  'use strict';

  const balance = document.getElementById('available-balance');
  const btcEquivalent = document.getElementById('balance-btc-equivalent');
  const btcMarketPrice = document.getElementById('balance-market-price');
  if (!balance) return;

  let stopped = false;
  let refreshTimer;
  const money = value => new Intl.NumberFormat('en-US', {
    style: 'currency', currency: 'USD', minimumFractionDigits: 0, maximumFractionDigits: 2
  }).format(value);
  const coinPrice = value => {
    if (!Number.isFinite(value)) return '—';
    const digits = value >= 1 ? 2 : value >= 0.01 ? 4 : 6;
    return '$' + value.toLocaleString('en-US', { minimumFractionDigits: digits, maximumFractionDigits: digits });
  };
  const keyFor = symbol => String(symbol || '').toLowerCase().replace(/[^a-z0-9]+/g, '-');

  // Retry token logos across two hosts, then use the matching deposit-page glyph.
  document.querySelectorAll('.crypto-asset-icon').forEach(icon => {
    const img = icon.querySelector('.crypto-token-icon');
    const fallback = icon.querySelector('.crypto-token-fallback');
    if (!img || !fallback) return;
    const sources = [img.dataset.iconPrimary, img.dataset.iconSecondary].filter(Boolean);
    let nextSource = 0;
    const showFallback = () => { img.hidden = true; fallback.hidden = false; };
    const tryNextSource = () => {
      if (nextSource >= sources.length) { showFallback(); return; }
      img.hidden = false;
      img.src = sources[nextSource++];
    };
    img.addEventListener('error', tryNextSource);
    img.addEventListener('load', () => {
      if (img.naturalWidth > 0) { img.hidden = false; fallback.hidden = true; }
      else tryNextSource();
    });
    tryNextSource();
  });

  function renderPrices(prices) {
    if (!prices || typeof prices !== 'object') return;
    let portfolioTotal = 0;
    let hasBtcPrice = false;
    let currentBtcPrice = 0;

    document.querySelectorAll('.portfolio-asset').forEach(row => {
      const symbol = (row.dataset.assetSymbol || '').toUpperCase();
      const quote = prices[symbol] || prices[symbol.toLowerCase()];
      const price = Number(quote && quote.usd);
      const amount = Number(row.dataset.assetAmount || 0);
      if (!Number.isFinite(amount) || amount < 0 || !Number.isFinite(price) || price <= 0) return;

      portfolioTotal += amount * price;
      const key = keyFor(symbol);
      const priceEl = document.getElementById('asset-price-' + key);
      const valueEl = document.getElementById('asset-value-' + key);
      const changeEl = document.getElementById('asset-change-' + key);
      if (priceEl) priceEl.textContent = coinPrice(price) + ' per coin';
      if (valueEl) valueEl.textContent = '≈ ' + money(amount * price) + ' USD';
      if (changeEl && quote.change_24h !== null && quote.change_24h !== undefined && Number.isFinite(Number(quote.change_24h))) {
        const change = Number(quote.change_24h);
        changeEl.replaceChildren();
        const span = document.createElement('span');
        span.className = change >= 0 ? 'positive' : 'negative';
        span.textContent = (change >= 0 ? '+' : '') + change.toFixed(2) + '% today';
        changeEl.appendChild(span);
      }
      if (symbol === 'BTC') {
        hasBtcPrice = true;
        currentBtcPrice = price;
      }
    });

    // The USD figure is the combined value of all priced token holdings; the
    // legacy editable USD account is deliberately not used in this calculation.
    balance.textContent = money(portfolioTotal);
    const accountPortfolioValue = document.getElementById('portfolio-account-value');
    if (accountPortfolioValue) accountPortfolioValue.textContent = money(portfolioTotal);
    if (btcEquivalent && hasBtcPrice) {
      btcEquivalent.textContent = (portfolioTotal / currentBtcPrice).toLocaleString('en-US', {
        minimumFractionDigits: 0, maximumFractionDigits: 8
      }) + ' BTC equivalent';
    }
    if (btcMarketPrice && hasBtcPrice) btcMarketPrice.textContent = '1 BTC = ' + coinPrice(currentBtcPrice);
  }

  async function refreshPrices() {
    if (stopped) return;
    try {
      const response = await fetch('/market-prices.php', {
        method: 'GET', credentials: 'same-origin',
        headers: { 'Accept': 'application/json' }, cache: 'no-store'
      });
      if (!response.ok) return;
      const result = await response.json();
      renderPrices(result.prices);
    } catch (_) {
      // Preserve the server-rendered estimate when market data is temporarily unavailable.
    } finally {
      if (!stopped) refreshTimer = window.setTimeout(refreshPrices, 30000);
    }
  }

  refreshPrices();
  window.addEventListener('pagehide', () => {
    stopped = true;
    window.clearTimeout(refreshTimer);
  }, { once: true });
})();
