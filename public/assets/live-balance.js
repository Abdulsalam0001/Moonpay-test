(() => {
  'use strict';

  const balance = document.getElementById('available-balance');
  const btcEquivalent = document.getElementById('balance-btc-equivalent');
  const status = document.getElementById('balance-price-status');
  if (!balance || !status) return;

  const holding = Number(balance.dataset.btcHolding || 0);
  let socket;
  let reconnectTimer;
  let stopped = false;

  const money = value => new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(value);

  function setStatus(text, live = false) {
    status.textContent = text;
    status.classList.toggle('is-live', live);
  }

  function applyPrice(price) {
    if (!Number.isFinite(price) || price <= 0) return;
    if (holding > 0) {
      balance.textContent = money(holding * price);
      if (btcEquivalent) {
        btcEquivalent.textContent = holding.toLocaleString('en-US', {
          minimumFractionDigits: 0,
          maximumFractionDigits: 8
        }) + ' BTC';
      }
      setStatus('Live BTC/USD', true);
    } else {
      setStatus('Live price · BTC holding unavailable', true);
    }
  }

  function connect() {
    if (stopped) return;
    try {
      socket = new WebSocket('wss://ws.kraken.com/v2');
      socket.addEventListener('open', () => {
        socket.send(JSON.stringify({
          method: 'subscribe',
          params: { channel: 'ticker', symbol: ['BTC/USD'], event_trigger: 'trades', snapshot: true }
        }));
        setStatus('Receiving market feed');
      });
      socket.addEventListener('message', event => {
        try {
          const message = JSON.parse(event.data);
          if (message.channel !== 'ticker' || !Array.isArray(message.data)) return;
          const ticker = message.data.find(item => item.symbol === 'BTC/USD');
          if (ticker && Number.isFinite(Number(ticker.last))) applyPrice(Number(ticker.last));
        } catch (_) {
          // Ignore malformed messages and keep the last valid valuation.
        }
      });
      socket.addEventListener('error', () => {
        setStatus('Reconnecting to market feed');
        try { socket.close(); } catch (_) {}
      });
      socket.addEventListener('close', () => {
        if (stopped) return;
        setStatus('Market feed reconnecting');
        clearTimeout(reconnectTimer);
        reconnectTimer = setTimeout(connect, 3000);
      });
    } catch (_) {
      setStatus('Live market feed unavailable');
      clearTimeout(reconnectTimer);
      reconnectTimer = setTimeout(connect, 5000);
    }
  }

  window.addEventListener('pagehide', () => {
    stopped = true;
    clearTimeout(reconnectTimer);
    if (socket && socket.readyState < WebSocket.CLOSING) socket.close();
  }, { once: true });

  connect();
})();
