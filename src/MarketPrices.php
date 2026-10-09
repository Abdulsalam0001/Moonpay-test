<?php
declare(strict_types=1);

/**
 * Fetch public USD spot prices for supported crypto assets.
 * Results are cached in the current session for 60 seconds to reduce API calls.
 * This is display-only market data, not an execution or settlement price.
 */
function crypto_market_prices(): array
{
    $cacheKey = 'crypto_market_prices_usd';
    $cachedAtKey = 'crypto_market_prices_usd_at';
    $now = time();

    if (
        isset($_SESSION[$cacheKey], $_SESSION[$cachedAtKey]) &&
        is_array($_SESSION[$cacheKey]) &&
        ($now - (int) $_SESSION[$cachedAtKey]) < 60
    ) {
        return $_SESSION[$cacheKey];
    }

    $ids = 'bitcoin,ethereum,tether,solana,ripple';
    $url = 'https://api.coingecko.com/api/v3/simple/price?ids=' .
        rawurlencode($ids) . '&vs_currencies=usd&include_24hr_change=true';

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 4,
            'header' => "Accept: application/json\r\nUser-Agent: MoonPayDemo/1.0\r\n",
            'ignore_errors' => true,
        ],
    ]);

    $response = @file_get_contents($url, false, $context);
    if (!is_string($response) || $response === '') {
        return isset($_SESSION[$cacheKey]) && is_array($_SESSION[$cacheKey])
            ? $_SESSION[$cacheKey]
            : [];
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        return isset($_SESSION[$cacheKey]) && is_array($_SESSION[$cacheKey])
            ? $_SESSION[$cacheKey]
            : [];
    }

    $mapping = [
        'BTC' => 'bitcoin',
        'BITCOIN' => 'bitcoin',
        'ETH' => 'ethereum',
        'ETHEREUM' => 'ethereum',
        'USDT' => 'tether',
        'TETHER' => 'tether',
        'SOL' => 'solana',
        'SOLANA' => 'solana',
        'XRP' => 'ripple',
    ];

    $prices = [];
    foreach ($mapping as $symbol => $id) {
        $price = $decoded[$id]['usd'] ?? null;
        $change = $decoded[$id]['usd_24h_change'] ?? null;

        if (is_numeric($price)) {
            $prices[$symbol] = [
                'usd' => (float) $price,
                'change_24h' => is_numeric($change) ? (float) $change : null,
            ];
        }
    }

    if ($prices) {
        $_SESSION[$cacheKey] = $prices;
        $_SESSION[$cachedAtKey] = $now;
        return $prices;
    }

    return isset($_SESSION[$cacheKey]) && is_array($_SESSION[$cacheKey])
        ? $_SESSION[$cacheKey]
        : [];
}

function crypto_price_for_symbol(array $prices, string $symbol): ?array
{
    $key = strtoupper(trim($symbol));
    return $prices[$key] ?? null;
}

function format_crypto_usd(float $price): string
{
    if ($price >= 1000) {
        return '$' . number_format($price, 2);
    }
    if ($price >= 1) {
        return '$' . number_format($price, 2);
    }
    if ($price >= 0.01) {
        return '$' . number_format($price, 4);
    }
    return '$' . number_format($price, 6);
}
