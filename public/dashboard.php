<?php
require_once __DIR__.'/../src/bootstrap.php';
require_once __DIR__.'/../src/MarketPrices.php';
$user=require_auth();
$pdo=Database::connection();

if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='complete_onboarding'){
    verify_csrf($_POST['csrf_token']??null);
    $_SESSION['onboarding_seen']=true;
    header('Location: /dashboard.php');
    exit;
}

$showOnboarding=empty($_SESSION['onboarding_seen']);

$a=$pdo->prepare('SELECT currency,balance FROM accounts WHERE user_id=:id ORDER BY currency');
$a->execute(['id'=>$user['id']]);
$accounts=$a->fetchAll();

$t=$pdo->prepare(
    'SELECT type,description,amount,currency,status,created_at
     FROM transactions
     WHERE user_id=:id
     ORDER BY created_at DESC
     LIMIT 8'
);
$t->execute(['id'=>$user['id']]);
$transactions=$t->fetchAll();

$tokens=[];
try{
    $tokenStmt=$pdo->prepare(
        'SELECT symbol,name,balance
         FROM user_tokens
         WHERE user_id=:id
         ORDER BY symbol'
    );
    $tokenStmt->execute(['id'=>$user['id']]);
    $tokens=$tokenStmt->fetchAll();
}catch(PDOException $e){
    $tokens=[];
}

$marketPrices=crypto_market_prices();

$total=0;
foreach($accounts as $x){
    if($x['currency']==='USD'){
        $total+=(float)$x['balance'];
    }
}

// Persist a one-time BTC holding based on the user's current USD balance.
// Subsequent market updates change only the displayed USD valuation, not the BTC amount.
$btcPrice=crypto_price_for_symbol($marketPrices,'BTC');
if($btcPrice && $btcPrice['usd']>0){
    $seedBtc=$pdo->prepare(
        'INSERT INTO user_tokens(user_id,symbol,name,balance)
         VALUES(:user_id,\'BTC\',\'Bitcoin\',:balance)
         ON CONFLICT(user_id,symbol) DO UPDATE
         SET balance=EXCLUDED.balance
         WHERE user_tokens.balance=0'
    );
    $seedBtc->execute([
        'user_id'=>$user['id'],
        'balance'=>number_format($total/$btcPrice['usd'],8,'.','')
    ]);
}

// Make the supported portfolio assets visible from the start without inventing holdings.
$seedAsset=$pdo->prepare(
    'INSERT INTO user_tokens(user_id,symbol,name,balance)
     VALUES(:user_id,:symbol,:name,0)
     ON CONFLICT(user_id,symbol) DO NOTHING'
);
foreach([
    ['BTC','Bitcoin'],
    ['ETH','Ethereum'],
    ['USDT','Tether'],
    ['SOL','Solana'],
    ['XRP','XRP'],
] as [$symbol,$name]){
    $seedAsset->execute([
        'user_id'=>$user['id'],
        'symbol'=>$symbol,
        'name'=>$name,
    ]);
}

$btcStmt=$pdo->prepare("SELECT balance FROM user_tokens WHERE user_id=:id AND UPPER(symbol)='BTC' LIMIT 1");
$btcStmt->execute(['id'=>$user['id']]);
$btcHolding=(float)($btcStmt->fetchColumn() ?: 0);

// Re-read tokens so a first-visit BTC holding appears in the portfolio list.
try{
    $tokenStmt=$pdo->prepare('SELECT symbol,name,balance FROM user_tokens WHERE user_id=:id ORDER BY symbol');
    $tokenStmt->execute(['id'=>$user['id']]);
    $tokens=$tokenStmt->fetchAll();
}catch(PDOException $e){
    $tokens=[];
}
$availableBalance=($btcPrice && $btcHolding>0)
    ? $btcHolding*(float)$btcPrice['usd']
    : $total;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Overview · MoonPay</title>
<link rel="stylesheet" href="/assets/app.css?v=20261010">
</head>
<body>
<header class="topbar">
  <a class="brand" href="/dashboard.php"><span class="brand-mark">M</span><span>moonpay</span></a>
  <nav>
    <a class="nav-active" href="/dashboard.php">Overview</a>
    <a href="/help.php">Help</a>
    <?php if(($user['role']??'')==='admin'):?><a href="/admin.php">Admin</a><?php endif;?>
    <a href="/logout.php">Log out</a>
  </nav>
</header>

<main class="shell financial-shell">
  <div class="hero-row">
    <div>
      <p class="eyebrow">Overview</p>
      <h1>Good to see you, <?=e($user['name'])?>.</h1>
      <p class="muted">Here is your account overview and recent financial activity.</p>
    </div>
  </div>

  <section class="balance-card financial-balance">
    <div><span>Estimated portfolio value</span><small class="balance-label">BTC holding valued at market price</small></div>
    <strong id="available-balance" data-btc-holding="<?=e(rtrim(rtrim(number_format($btcHolding,8,'.',''),'0'),'.'))?>" data-fallback-usd="<?=e((string)$total)?>">$<?=number_format($availableBalance,0)?></strong>
    <div class="balance-meta"><span id="balance-btc-equivalent"><?=number_format($btcHolding,8)?> BTC</span><span id="balance-market-price"><?= $btcPrice ? '1 BTC = '.e(format_crypto_usd((float)$btcPrice['usd'])) : 'Market price refreshing' ?></span></div>
  </section>

  <section class="quick-actions">
    <a href="/account-action.php?action=buy" class="quick-action"><span>＋</span><strong>Buy crypto</strong><small>Purchase assets</small></a>
    <a href="/account-action.php?action=send" class="quick-action"><span>↗</span><strong>Send</strong><small>Transfer assets</small></a>
    <a href="/account-action.php?action=deposit" class="quick-action"><span>↓</span><strong>Deposit</strong><small>View wallet address</small></a>
    <a href="/help.php" class="quick-action"><span>?</span><strong>Get help</strong><small>Wallet & account guidance</small></a>
  </section>

  <div class="grid-2 financial-grid">
    <section class="panel">
      <div class="panel-head"><h2>Accounts</h2><span><?=count($accounts)?> currencies</span></div>
      <?php foreach($accounts as $x): ?>
        <div class="asset-row">
          <div class="asset-icon"><?=e(substr($x['currency'],0,1))?></div>
          <div class="asset-copy"><strong><?=e($x['currency'])?></strong><small>Available balance</small></div>
          <strong><?=number_format((float)$x['balance'],0)?></strong>
        </div>
      <?php endforeach; ?>
      <?php if(!$accounts): ?><p class="muted empty">No balances yet.</p><?php endif; ?>
    </section>

    <section class="panel">
      <div class="panel-head"><h2>Recent activity</h2><span>Latest</span></div>
      <?php foreach($transactions as $x): ?>
        <div class="transaction-row">
          <div><strong><?=e($x['description'])?></strong><small><?=e(ucfirst($x['status']))?> · <?=e(date('M j, Y',strtotime($x['created_at'])))?></small></div>
          <strong class="<?=$x['type']==='withdrawal'?'negative':'positive'?>"><?=$x['type']==='withdrawal'?'-':'+'?><?=e($x['currency'])?> <?=number_format((float)$x['amount'],2)?></strong>
        </div>
      <?php endforeach; ?>
      <?php if(!$transactions): ?><p class="muted empty">No transactions yet.</p><?php endif; ?>
    </section>
  </div>

  <section class="panel portfolio-assets-section" style="margin-top:22px">
    <div class="panel-head"><div><p class="eyebrow">Your portfolio</p><h2>Crypto assets</h2></div><span><?=count($tokens)?> assets</span></div>
    <?php foreach($tokens as $token): ?>
      <?php
        $marketPrice=crypto_price_for_symbol($marketPrices,(string)$token['symbol']);
        $tokenKey=strtolower(preg_replace('/[^a-z0-9]+/i','-',(string)$token['symbol']));
        $tokenAmount=(float)$token['balance'];
      ?>
      <div class="portfolio-asset" data-asset-symbol="<?=e(strtoupper((string)$token['symbol']))?>" data-asset-amount="<?=e((string)$tokenAmount)?>">
        <div class="asset-icon crypto-asset-icon">
          <img class="crypto-token-icon" src="https://raw.githubusercontent.com/spothq/cryptocurrency-icons/master/128/color/<?=e(strtolower((string)$token['symbol']))?>.png" alt="" loading="lazy" decoding="async">
          <span class="crypto-token-fallback"><?=e(substr(strtoupper((string)$token['symbol']),0,1))?></span>
        </div>
        <div class="asset-copy">
          <strong><?=e($token['name'])?> <span class="portfolio-symbol"><?=e(strtoupper((string)$token['symbol']))?></span></strong>
          <small class="portfolio-price" id="asset-price-<?=$tokenKey?>"><?= $marketPrice ? e(format_crypto_usd((float)$marketPrice['usd'])).' per coin' : 'Price updating' ?></small>
          <small class="portfolio-change" id="asset-change-<?=$tokenKey?>"><?php if($marketPrice && $marketPrice['change_24h']!==null): ?><span class="<?=$marketPrice['change_24h']>=0?'positive':'negative'?>"><?=($marketPrice['change_24h']>=0?'+':'')?><?=number_format($marketPrice['change_24h'],2)?>% today</span><?php else: ?>24-hour change not available<?php endif; ?></small>
        </div>
        <div class="token-balance-value">
          <strong id="asset-amount-<?=$tokenKey?>"><?=rtrim(rtrim(number_format($tokenAmount,8,'.',''),'0'),'.')?></strong>
          <small id="asset-value-<?=$tokenKey?>"><?= $marketPrice ? '≈ '.e(format_crypto_usd($tokenAmount*(float)$marketPrice['usd'])).' USD' : 'Value updating' ?></small>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if(!$tokens): ?><p class="muted empty">No crypto assets to display yet.</p><?php endif; ?>
  </section>
</main>
<script src="/assets/live-balance.js?v=20261010" defer></script>

<?php if($showOnboarding): ?>
<div class="onboarding-backdrop" id="security-onboarding" role="dialog" aria-modal="true" aria-labelledby="onboarding-title">
  <section class="onboarding-modal">
    <div class="onboarding-top"><span class="onboarding-brand"><span class="brand-mark">M</span> moonpay</span><span id="onboarding-count">1 / 4</span></div>
    <div class="onboarding-track" id="onboarding-track">
      <article class="onboarding-slide is-active" data-slide="0"><div class="onboarding-icon">◉</div><p class="eyebrow">Wallet basics</p><h2 id="onboarding-title">Your wallet is non-custodial</h2><p>Your wallet credentials control access to the associated blockchain assets. Your account login and wallet ownership are not the same thing.</p></article>
      <article class="onboarding-slide" data-slide="1"><div class="onboarding-icon">!</div><p class="eyebrow">Account access</p><h2>Restrictions are different from a blockchain freeze</h2><p>An account restriction can limit services while the underlying blockchain continues to record the wallet's assets and transactions.</p></article>
      <article class="onboarding-slide" data-slide="2"><div class="onboarding-icon">⌁</div><p class="eyebrow">Wallet security</p><h2>Keep your recovery phrase private</h2><p>Never share your recovery phrase, private key, password, or security codes with someone claiming to provide support.</p></article>
      <article class="onboarding-slide" data-slide="3"><div class="onboarding-icon">✓</div><p class="eyebrow">Stay protected</p><h2>You are in control</h2><p>Review wallet addresses and networks before sending assets, and treat unexpected recovery or payment requests with caution.</p></article>
    </div>
    <div class="onboarding-dots"><span class="is-active"></span><span></span><span></span><span></span></div>
    <div class="onboarding-actions">
      <button class="button button-dark" type="button" id="onboarding-next">Continue</button>
      <form method="post" id="onboarding-complete">
        <input type="hidden" name="action" value="complete_onboarding">
        <input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>">
        <button class="button button-dark" type="submit">Got it — Continue to dashboard</button>
      </form>
      <button class="onboarding-skip" type="submit" form="onboarding-complete">Skip for now</button>
    </div>
  </section>
</div>
<script src="/assets/dashboard.js" defer></script>
<?php endif; ?>
</body>
</html>