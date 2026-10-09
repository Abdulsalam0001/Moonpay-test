<?php
declare(strict_types=1);

/**
 * Display-only USD market snapshots. Try CoinGecko first, then Coinbase spot
 * prices for any missing symbols, and finally reuse the last known session
 * snapshot rather than replacing useful values with blanks.
 */
function crypto_market_prices(): array
{
    $cacheKey = 'crypto_market_prices_usd';
    $cachedAtKey = 'crypto_market_prices_usd_at';
    $now = time();

    if (isset($_SESSION[$cacheKey], $_SESSION[$cachedAtKey]) &&
        is_array($_SESSION[$cacheKey]) &&
        ($now - (int) $_SESSION[$cachedAtKey]) < 60) {
        return $_SESSION[$cacheKey];
    }

    $previous = isset($_SESSION[$cacheKey]) && is_array($_SESSION[$cacheKey])
        ? $_SESSION[$cacheKey]
        : [];

    $symbols = [
        'BTC' => ['id' => 'bitcoin', 'name' => 'Bitcoin'],
        'ETH' => ['id' => 'ethereum', 'name' => 'Ethereum'],
        'USDT' => ['id' => 'tether', 'name' => 'Tether'],
        'SOL' => ['id' => 'solana', 'name' => 'Solana'],
        'XRP' => ['id' => 'ripple', 'name' => 'XRP'],
    ];
    $prices = [];

    $ids = implode(',', array_column($symbols, 'id'));
    $url = 'https://api.coingecko.com/api/v3/simple/price?ids=' .
        rawurlencode($ids) . '&vs_currencies=usd&include_24hr_change=true';
    $response = crypto_market_http_get($url);
    $decoded = is_string($response) ? json_decode($response, true) : null;

    if (is_array($decoded)) {
        foreach ($symbols as $symbol => $meta) {
            $price = $decoded[$meta['id']]['usd'] ?? null;
            $change = $decoded[$meta['id']]['usd_24h_change'] ?? null;
            if (is_numeric($price) && (float) $price > 0) {
                $prices[$symbol] = [
                    'usd' => (float) $price,
                    'change_24h' => is_numeric($change) ? (float) $change : null,
                    'source' => 'CoinGecko',
                    'stale' => false,
                ];
            }
        }
    }

    // Fallback provider: Coinbase spot endpoint, requested only for missing assets.
    foreach ($symbols as $symbol => $meta) {
        if (isset($prices[$symbol])) {
            continue;
        }
        $fallbackUrl = 'https://api.coinbase.com/v2/prices/' .
            rawurlencode($symbol . '-USD') . '/spot';
        $fallbackResponse = crypto_market_http_get($fallbackUrl);
        $fallbackDecoded = is_string($fallbackResponse)
            ? json_decode($fallbackResponse, true)
            : null;
        $amount = $fallbackDecoded['data']['amount'] ?? null;
        if (is_numeric($amount) && (float) $amount > 0) {
            $prices[$symbol] = [
                'usd' => (float) $amount,
                'change_24h' => $previous[$symbol]['change_24h'] ?? null,
                'source' => 'Coinbase',
                'stale' => false,
            ];
        }
    }

    // Last-resort fallback: retain the last good value and mark it stale.
    foreach ($symbols as $symbol => $_meta) {
        if (!isset($prices[$symbol]) && isset($previous[$symbol]['usd']) &&
            is_numeric($previous[$symbol]['usd']) && (float) $previous[$symbol]['usd'] > 0) {
            $prices[$symbol] = [
                'usd' => (float) $previous[$symbol]['usd'],
                'change_24h' => $previous[$symbol]['change_24h'] ?? null,
                'source' => $previous[$symbol]['source'] ?? 'Last known price',
                'stale' => true,
            ];
        }
    }

    if ($prices) {
        $_SESSION[$cacheKey] = $prices;
        $_SESSION[$cachedAtKey] = $now;
        return $prices;
    }

    return $previous;
}

function crypto_market_http_get(string $url): ?string
{
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 3,
            'header' => "Accept: application/json\r\nUser-Agent: MoonPayDemo/1.0\r\n",
            'ignore_errors' => true,
        ],
    ]);
    $response = @file_get_contents($url, false, $context);
    return is_string($response) && $response !== '' ? $response : null;
}

function crypto_price_for_symbol(array $prices, string $symbol): ?array
{
    $key = strtoupper(trim($symbol));
    return isset($prices[$key]) && is_array($prices[$key]) ? $prices[$key] : null;
}

function format_crypto_usd(float $price): string
{
    if ($price >= 1) {
        return '$' . number_format($price, 2);
    }
    if ($price >= 0.01) {
        return '$' . number_format($price, 4);
    }
    return '$' . number_format($price, 6);
}
