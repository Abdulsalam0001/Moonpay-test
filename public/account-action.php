<?php
require_once __DIR__.'/../src/bootstrap.php';
$user=require_auth();

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
    <section class="action-panel">
      <p class="eyebrow">Receive crypto</p>
      <h1>Deposit</h1>
      <p class="muted">Send Bitcoin to the wallet address below.</p>

      <div class="deposit-address-card">
        <div class="deposit-address-top">
          <div>
            <strong>Bitcoin</strong>
            <span>Wallet address</span>
          </div>
          <span class="address-network">BTC</span>
        </div>
        <code id="deposit-address"><?=e($walletAddress)?></code>
        <button class="button button-dark copy-address" type="button" data-copy-target="deposit-address">
          <span>Copy address</span>
        </button>
        <p class="copy-status" id="copy-status" aria-live="polite"></p>
      </div>

      <p class="action-note">Check the network and address carefully before sending assets.</p>
      <a class="button button-light" href="/dashboard.php">Back to overview</a>
    </section>
  <?php elseif($action==='send'): ?>
    <section class="action-panel">
      <p class="eyebrow">Send crypto</p>
      <h1>Send</h1>
      <p class="muted">Enter the recipient wallet address to continue.</p>

      <label class="wallet-field">
        <span>Recipient wallet address</span>
        <input type="text" value="<?=e($walletAddress)?>" aria-label="Recipient wallet address" readonly>
      </label>
      <p class="action-note">This prototype does not submit or broadcast a blockchain transaction.</p>
      <a class="button button-dark" href="/dashboard.php">Back to overview</a>
    </section>
  <?php else: ?>
    <section class="action-panel">
      <p class="eyebrow">Account action</p>
      <h1><?=e($title)?></h1>
      <p class="muted">This demo action is not connected to a live payment rail or blockchain.</p>
      <a class="button button-dark" href="/dashboard.php">Back to overview</a>
    </section>
  <?php endif; ?>
</main>
<script src="/assets/account-action.js" defer></script>
</body>
</html>