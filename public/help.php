<?php
require_once __DIR__.'/../src/bootstrap.php';
$user=require_auth();
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Help · MoonPay</title><link rel="stylesheet" href="/assets/app.css"></head>
<body>
<header class="topbar"><a class="brand" href="/dashboard.php"><span class="brand-mark">M</span><span>moonpay</span></a><nav><a href="/dashboard.php">Overview</a><a class="nav-active" href="/help.php">Help</a><?php if(($user['role']??'')==='admin' && empty($user['demo'])):?><a href="/admin.php">Admin</a><?php endif;?><a href="/logout.php">Log out</a></nav></header>
<main class="shell help-shell">
  <div class="help-hero"><p class="eyebrow">Help center</p><h1>Understand your wallet.</h1><p class="muted">Useful guidance about wallet ownership, account restrictions, blockchain transactions, and staying safe.</p></div>
  <section class="help-grid">
    <article class="help-card"><div class="help-icon">◉</div><p class="eyebrow">Wallet ownership</p><h2>Non-custodial wallets</h2><p>A non-custodial wallet means control of the wallet credentials belongs to the wallet holder. Your account login and your blockchain wallet are related, but they are not the same thing.</p></article>
    <article class="help-card"><div class="help-icon">!</div><p class="eyebrow">Account access</p><h2>Restrictions are not a blockchain freeze</h2><p>An account restriction can limit access to a service or certain features. It does not automatically mean the service provider has control over or has frozen assets on the underlying blockchain.</p></article>
    <article class="help-card"><div class="help-icon">⌁</div><p class="eyebrow">Recovery</p><h2>Protect your recovery phrase</h2><p>Your recovery phrase or private key can provide control over a wallet. Never send it to support, enter it into an unfamiliar website, or share it with anyone asking for it.</p></article>
    <article class="help-card"><div class="help-icon">↗</div><p class="eyebrow">Transactions</p><h2>Check the network before sending</h2><p>Before confirming a transaction, verify the recipient address, asset, and blockchain network. Blockchain transfers can be irreversible once confirmed.</p></article>
    <article class="help-card"><div class="help-icon">✓</div><p class="eyebrow">Security</p><h2>Support should never need your secrets</h2><p>Do not share passwords, one-time codes, private keys, recovery phrases, or authentication codes with someone claiming to be support.</p></article>
    <article class="help-card"><div class="help-icon">i</div><p class="eyebrow">Demo account</p><h2>About this environment</h2><p>This demo uses simulated balances and activity. It does not connect the displayed balances to a live blockchain, and its mainnet actions are intentionally unavailable.</p></article>
  </section>
  <section class="help-warning"><strong>Security reminder</strong><p>If someone asks for your recovery phrase, private key, password, or one-time security code to unlock or recover funds, do not provide it.</p></section>
</main>
</body></html>