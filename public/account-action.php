<?php
require_once __DIR__.'/../src/bootstrap.php';
$user=require_auth();
$pdo=Database::connection();

$action=trim((string)($_GET['action']??''));
$labels=[
 'deposit'=>'Deposit',
 'withdraw'=>'Withdraw',
 'send'=>'Send',
 'receive'=>'Receive',
 'buy'=>'Buy crypto',
 'transfer'=>'Transfer'
];
$title=$labels[$action]??'Account action';
$restricted=($user['status']??'active')!=='active';
$walletAddress='bc1q4pj3qpjnjvt7h7y475jff5fgu2n2twl5575mnv';

$sendTokens=[];
if($action==='send' && !$restricted){
    try{
        $tokenStmt=$pdo->prepare(
            'SELECT symbol,name,balance
             FROM user_tokens
             WHERE user_id=:id AND balance > 0
             ORDER BY symbol'
        );
        $tokenStmt->execute(['id'=>$user['id']]);
        $sendTokens=$tokenStmt->fetchAll();
    }catch(PDOException $e){
        $sendTokens=[];
    }
}

$depositHistory=[];
if(($action==='deposit' || $action==='receive') && !$restricted){
    $historyStmt=$pdo->prepare(
        "SELECT description,amount,currency,status,created_at
         FROM transactions
         WHERE user_id=:id AND type='deposit'
         ORDER BY created_at DESC
         LIMIT 8"
    );
    $historyStmt->execute(['id'=>$user['id']]);
    $depositHistory=$historyStmt->fetchAll();
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=e($title)?> · MoonPay</title>
<link rel="stylesheet" href="/assets/app.css">
</head>
<body>
<header class="topbar">
  <a class="brand" href="/dashboard.php"><span class="brand-mark">M</span><span>moonpay</span></a>
  <nav><a href="/dashboard.php">Overview</a><a href="/help.php">Help</a><?php if(($user['role']??'')==='admin'):?><a href="/admin.php">Admin</a><?php endif;?><a href="/logout.php">Log out</a></nav>
</header>

<main class="shell action-shell">
  <?php if($restricted): ?>
    <section class="action-restricted">
      <div class="restricted-icon">!</div>
      <p class="eyebrow">Account restricted</p>
      <h1>Account currently restricted due to inactivity.</h1>
      <p class="muted">Your account is currently restricted, so <?=e(strtolower($title))?> and other account functions are unavailable.</p>
      <div class="action-restricted-copy">
        <strong>Activate account to resume functions</strong>
        <span>Contact support to review and reactivate the account. Activation is controlled by the account administrator.</span>
      </div>
      <a class="button button-dark" href="/help.php">Get help</a>
    </section>

  <?php elseif($action==='deposit' || $action==='receive'): ?>
    <section class="deposit-assets-section">
      <div class="panel-head"><div><p class="eyebrow">Choose an asset</p><h2>Crypto assets</h2></div><span>1 available</span></div>
      <div class="deposit-asset-list" role="list" aria-label="Crypto assets">
        <a class="deposit-asset is-selected" href="/account-action.php?action=deposit" role="listitem" aria-current="page">
          <span class="deposit-asset-icon btc-icon">₿</span><span class="deposit-asset-copy"><strong>Bitcoin</strong><small>BTC · Bitcoin network</small></span><span class="deposit-asset-status" aria-label="Selected"></span><span class="deposit-asset-arrow">›</span>
        </a>
        <div class="deposit-asset is-disabled" role="listitem" aria-disabled="true"><span class="deposit-asset-icon eth-icon">◆</span><span class="deposit-asset-copy"><strong>Ethereum</strong><small>ETH · Placeholder</small></span><span class="deposit-asset-status" aria-hidden="true"></span></div>
        <div class="deposit-asset is-disabled" role="listitem" aria-disabled="true"><span class="deposit-asset-icon usdt-icon">₮</span><span class="deposit-asset-copy"><strong>Tether</strong><small>USDT · Placeholder</small></span><span class="deposit-asset-status">Soon</span></div>
        <div class="deposit-asset is-disabled" role="listitem" aria-disabled="true"><span class="deposit-asset-icon sol-icon">◎</span><span class="deposit-asset-copy"><strong>Solana</strong><small>SOL · Placeholder</small></span><span class="deposit-asset-status">Soon</span></div>
        <div class="deposit-asset is-disabled" role="listitem" aria-disabled="true"><span class="deposit-asset-icon xrp-icon">✕</span><span class="deposit-asset-copy"><strong>XRP</strong><small>XRP · Placeholder</small></span><span class="deposit-asset-status">Soon</span></div>
      </div>
    </section>

    <section class="action-panel">
      <p class="eyebrow">Receive crypto · Bitcoin</p>
      <h1>Deposit</h1>
      <p class="muted">Send Bitcoin to the wallet address below.</p>
      <div class="deposit-address-card">
        <div class="deposit-address-top">
          <div><strong>Bitcoin</strong><span>Wallet address</span></div>
          <span class="address-network">BTC</span>
        </div>
        <code id="deposit-address"><?=e($walletAddress)?></code>
        <button class="button button-dark copy-address" type="button" data-copy-target="deposit-address"><span>Copy address</span></button>
        <p class="copy-status" id="copy-status" aria-live="polite"></p>
      </div>
      <a class="button button-light" href="/dashboard.php">Back to overview</a>
    </section>

    <section class="panel deposit-history-panel">
      <div class="panel-head"><h2>Deposit history</h2><span>Latest deposits</span></div>
      <?php if($depositHistory): ?>
        <?php foreach($depositHistory as $x): ?>
          <div class="transaction-row">
            <div><strong><?=e($x['description'])?></strong><small><?=e(ucfirst($x['status']))?> · <?=e(date('M j, Y · g:i A',strtotime($x['created_at'])))?></small></div>
            <strong class="positive">+<?=e($x['currency'])?> <?=number_format((float)$x['amount'],2)?></strong>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="deposit-history-empty"><strong>No deposits yet</strong><p class="muted">Completed and pending deposits will appear here.</p></div>
      <?php endif; ?>
    </section>

  <?php elseif($action==='send'): ?>
    <section class="action-panel">
      <p class="eyebrow">Send crypto</p>
      <h1>Send</h1>
      <p class="muted">Enter the transfer details below. Only assets with an available balance can be sent.</p>
      <div class="send-form-heading"><span>Transfer information</span><small>Recipient and payment details</small></div>
      <?php if(!$sendTokens): ?>
        <div class="wallet-empty"><div><strong>No crypto available to send</strong><p class="muted">Only tokens with an available balance can be selected for a transfer.</p></div></div>
      <?php else: ?>
        <form class="send-form" id="send-form">
          <label class="wallet-field"><span>Recipient wallet address</span><input type="text" name="address" value="" placeholder="Enter recipient wallet address" autocomplete="off" spellcheck="false" required></label>
          <label class="wallet-field"><span>Asset</span>
            <select name="asset" id="send-asset" required>
              <?php foreach($sendTokens as $token): ?>
                <option value="<?=e($token['symbol'])?>" data-balance="<?=e((string)$token['balance'])?>" data-name="<?=e($token['name'])?>"><?=e($token['name'])?> (<?=e($token['symbol'])?>) · <?=rtrim(rtrim(number_format((float)$token['balance'],8,'.',''),'0'),'.')?> available</option>
              <?php endforeach; ?>
            </select>
          </label>
          <div class="send-available" id="send-available"></div>
          <label class="wallet-field"><span>Amount</span><div class="amount-input"><input type="number" name="amount" id="send-amount" min="0.00000001" step="0.00000001" placeholder="0.00000000" required><span id="send-symbol"><?=e($sendTokens[0]['symbol'])?></span></div></label>
          <button class="button button-dark send-button" type="submit">Review transfer</button>
          <p class="action-note">You can only send a token that is currently available in your account, and the amount cannot exceed its available balance.</p>
        </form>
      <?php endif; ?>
      <a class="button button-light" href="/dashboard.php">Back to overview</a>
    </section>
    <div class="onboarding-backdrop send-lock-backdrop" id="send-lock-modal" role="dialog" aria-modal="true" aria-labelledby="send-lock-title" hidden>
      <section class="onboarding-modal send-lock-modal">
        <div class="onboarding-top"><span class="onboarding-brand"><span class="brand-mark">M</span> moonpay</span><span>Account security</span></div>
        <div class="onboarding-track"><article class="onboarding-slide is-active">
          <div class="onboarding-icon">!</div><p class="eyebrow">Transfer unavailable</p><h2 id="send-lock-title">Account locked</h2>
          <p>Your account is currently restricted due to inactivity. Sending crypto is unavailable until your account is reviewed and reactivated.</p>
          <div class="action-restricted-copy"><strong>Activate account to resume functions</strong><span>Contact support to request an account review and reactivation.</span></div>
        </article></div>
        <div class="onboarding-actions"><a class="button button-dark" href="/help.php">Request account review</a><button class="onboarding-skip" type="button" id="close-send-lock">Go back</button></div>
      </section>
    </div>

  <?php elseif($action==='buy'): ?>
    <section class="action-panel">
      <p class="eyebrow">Purchase crypto</p>
      <h1>Buy crypto</h1>
      <p class="muted">Choose an asset and enter an amount to continue.</p>
      <form class="buy-form" id="buy-form">
        <label class="wallet-field"><span>Asset</span><select name="asset"><option>Bitcoin (BTC)</option><option>Ethereum (ETH)</option><option>USDC</option></select></label>
        <label class="wallet-field"><span>Amount</span><div class="amount-input"><input type="number" name="amount" min="0" step="0.01" placeholder="0.00" required><span>USD</span></div></label>
        <button class="button button-dark buy-button" type="submit">Continue to purchase</button>
      </form>
      <p class="action-note">This prototype does not process a real card payment or purchase.</p>
      <a class="button button-light" href="/dashboard.php">Back to overview</a>
    </section>
    <div class="onboarding-backdrop send-lock-backdrop" id="buy-lock-modal" role="dialog" aria-modal="true" aria-labelledby="buy-lock-title" hidden>
      <section class="onboarding-modal send-lock-modal">
        <div class="onboarding-top"><span class="onboarding-brand"><span class="brand-mark">M</span> moonpay</span><span>Account security</span></div>
        <div class="onboarding-track"><article class="onboarding-slide is-active">
          <div class="onboarding-icon">!</div><p class="eyebrow">Purchase unavailable</p><h2 id="buy-lock-title">Account locked</h2>
          <p>Your account is currently restricted due to inactivity. Buying crypto is unavailable until your account is reviewed and reactivated.</p>
          <div class="action-restricted-copy"><strong>Activate account to resume functions</strong><span>Contact support to request an account review and reactivation.</span></div>
        </article></div>
        <div class="onboarding-actions"><a class="button button-dark" href="/help.php">Request account review</a><button class="onboarding-skip" type="button" id="close-buy-lock">Go back</button></div>
      </section>
    </div>

  <?php else: ?>
    <section class="action-panel"><p class="eyebrow">Account action</p><h1><?=e($title)?></h1><p class="muted">This demo action is not connected to a live payment rail or blockchain.</p><a class="button button-dark" href="/dashboard.php">Back to overview</a></section>
  <?php endif; ?>
</main>
<script src="/assets/account-action.js" defer></script>
</body>
</html>