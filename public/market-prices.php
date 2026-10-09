<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/MarketPrices.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');

if (!current_user()) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

$prices = crypto_market_prices();

echo json_encode([
    'prices' => $prices,
    'updated_at' => time(),
], JSON_UNESCAPED_SLASHES);
