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
  <nav><a href="/dashboard.php">Overview</a><a href="/help.php">Help</a><?php if(($user['role']??'')==='admin' && empty($user['demo'])):?><a href="/admin.php">Admin</a><?php endif;?><a href="/logout.php">Log out</a></nav>
</header>
<main class="shell action-shell">
  <?php if($restricted): ?>
    <section class="action-restricted">
      <div class="restricted-icon">!</div>
      <p class="eyebrow">Account restricted</p>
      <h1>This account is restricted.</h1>
      <p class="muted">You cannot <?=e(strtolower($title))?> or perform account transactions while your account is suspended.</p>
      <a class="button button-dark" href="/help.php">Get help</a>
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
</body>
</html>