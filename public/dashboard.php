<?php
require_once __DIR__.'/../src/bootstrap.php';
$user=require_auth();$pdo=Database::connection();
$a=$pdo->prepare('SELECT currency,balance FROM accounts WHERE user_id=:id ORDER BY currency');$a->execute(['id'=>$user['id']]);$accounts=$a->fetchAll();
$t=$pdo->prepare('SELECT type,description,amount,currency,status,created_at FROM transactions WHERE user_id=:id ORDER BY created_at DESC LIMIT 8');$t->execute(['id'=>$user['id']]);$transactions=$t->fetchAll();
$total=0;foreach($accounts as $x)if($x['currency']==='USD')$total+=(float)$x['balance'];
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Dashboard · MoonPay</title><link rel="stylesheet" href="/assets/app.css"></head>
<body>
<header class="topbar">
  <a class="brand" href="/dashboard.php"><span class="brand-mark">M</span><span>moonpay</span></a>
  <nav><a class="nav-active" href="/dashboard.php">Overview</a><?php if($user['role']==='admin'):?><a href="/admin.php">Admin</a><?php endif;?><a href="/logout.php">Log out</a></nav>
</header>
<main class="shell">
  <div class="hero-row">
    <div><p class="eyebrow">Overview</p><h1>Your money, clearly.</h1><p class="muted">Welcome back, <?=e($user['name'])?>. Here is your latest account activity.</p></div>
    <button class="button button-dark" type="button">Buy crypto</button>
  </div>
  <section class="balance-card">
    <div><span>Total USD balance</span><small class="balance-label">Available balance</small></div>
    <strong>$<?=number_format($total,2)?></strong>
    <div class="balance-meta"><span>Portfolio</span><span>Live account view</span></div>
  </section>
  <div class="grid-2">
    <section class="panel"><div class="panel-head"><h2>Assets</h2><span><?=count($accounts)?> currencies</span></div>
      <?php foreach($accounts as $x):?><div class="asset-row"><div class="asset-icon"><?=e(substr($x['currency'],0,1))?></div><div class="asset-copy"><strong><?=e($x['currency'])?></strong><small>Available balance</small></div><strong><?=number_format((float)$x['balance'],2)?></strong></div><?php endforeach;?>
      <?php if(!$accounts):?><p class="muted empty">No balances yet.</p><?php endif;?>
    </section>
    <section class="panel"><div class="panel-head"><h2>Recent activity</h2><span>Latest</span></div>
      <?php foreach($transactions as $x):?><div class="transaction-row"><div><strong><?=e($x['description'])?></strong><small><?=e(ucfirst($x['status']))?> · <?=e(date('M j, Y',strtotime($x['created_at'])))?></small></div><strong class="<?=$x['type']==='withdrawal'?'negative':'positive'?>"><?=$x['type']==='withdrawal'?'-':'+'?><?=e($x['currency'])?> <?=number_format((float)$x['amount'],2)?></strong></div><?php endforeach;?>
      <?php if(!$transactions):?><p class="muted empty">No transactions yet.</p><?php endif;?>
    </section>
  </div>
</main>
</body></html>
