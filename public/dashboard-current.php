<?php
require_once __DIR__.'/../src/bootstrap.php';
$user=require_auth();

if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='complete_onboarding'){
    verify_csrf($_POST['csrf_token']??null);
    $_SESSION['onboarding_seen']=true;
    header('Location: /dashboard.php');
    exit;
}

$showOnboarding=empty($_SESSION['onboarding_seen']);

if(!empty($user['demo'])){
    $accounts=[
        ['currency'=>'USD','balance'=>125000.00],
        ['currency'=>'EUR','balance'=>18400.50],
        ['currency'=>'GBP','balance'=>9200.00],
        ['currency'=>'NGN','balance'=>2850000.00],
    ];
    $transactions=[
        ['type'=>'deposit','description'=>'Demo account funding','amount'=>25000,'currency'=>'USD','status'=>'completed','created_at'=>date('Y-m-d H:i:s',strtotime('-2 hours'))],
        ['type'=>'purchase','description'=>'Crypto purchase','amount'=>4200,'currency'=>'USD','status'=>'completed','created_at'=>date('Y-m-d H:i:s',strtotime('-1 day'))],
        ['type'=>'withdrawal','description'=>'Bank withdrawal','amount'=>1800,'currency'=>'USD','status'=>'completed','created_at'=>date('Y-m-d H:i:s',strtotime('-3 days'))],
    ];
}else{
    $pdo=Database::connection();
    $a=$pdo->prepare('SELECT currency,balance FROM accounts WHERE user_id=:id ORDER BY currency');$a->execute(['id'=>$user['id']]);$accounts=$a->fetchAll();
    $t=$pdo->prepare('SELECT type,description,amount,currency,status,created_at FROM transactions WHERE user_id=:id ORDER BY created_at DESC LIMIT 8');$t->execute(['id'=>$user['id']]);$transactions=$t->fetchAll();
}
$total=0;foreach($accounts as $x)if($x['currency']==='USD')$total+=(float)$x['balance'];
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Dashboard · MoonPay</title><link rel="stylesheet" href="/assets/app.css"></head>
<body>
<header class="topbar">
  <a class="brand" href="/dashboard.php"><span class="brand-mark">M</span><span>moonpay</span></a>
  <nav><a class="nav-active" href="/dashboard.php">Overview</a><?php if(($user['role']??'')==='admin' && empty($user['demo'])):?><a href="/admin.php">Admin</a><?php endif;?><a href="/logout.php">Log out</a></nav>
</header>
<main class="shell">
  <div class="hero-row">
    <div><p class="eyebrow">Overview</p><h1>Your money, clearly.</h1><p class="muted">Welcome back, <?=e($user['name'])?>. Here is your latest account activity.</p></div>
    <button class="button button-dark" type="button">Buy crypto</button>
  </div>
  <?php if(!empty($user['demo'])):?><div class="demo-notice"><div><strong>Demo environment</strong><span>This account is simulated and does not connect to a live blockchain.</span></div><span class="restriction-pill">Mainnet access restricted</span></div><?php endif;?>
  <?php if(!empty($user['demo'])):?><section class="restricted-card"><div class="restricted-icon">!</div><div><p class="eyebrow">Token access</p><h2>Mainnet access is restricted</h2><p class="muted">The tokens shown in this demo are simulated balances. Mainnet transfers, withdrawals, and blockchain transactions are unavailable.</p></div></section><?php endif;?>
  <section class="dashboard-cards">
    <article class="info-card info-card-blue"><div class="info-card-icon">◉</div><div><p class="eyebrow">Wallet & security</p><h2>Understand your wallet</h2><p>Your wallet is non-custodial. Account access and control of blockchain assets are not the same thing.</p><a href="#wallet-security">Learn about wallet security <span>→</span></a></div></article>
    <article class="info-card info-card-dark"><div class="info-card-icon">✓</div><div><p class="eyebrow">Stay protected</p><h2>Keep your recovery phrase private</h2><p>Never share recovery phrases, private keys, passwords, or security codes with anyone.</p><a href="#wallet-security">View security guidance <span>→</span></a></div></article>
  </section>
  <section class="balance-card">
    <div><span>Total USD balance</span><small class="balance-label">Available balance</small></div>
    <strong>$<?=number_format($total,2)?></strong>
    <div class="balance-meta"><span>Portfolio</span><span><?=!empty($user['demo'])?'Demo account · No mainnet access':'Live account view'?></span></div>
  </section>
  <section class="security-panel" id="wallet-security"><div class="security-panel-head"><div><p class="eyebrow">Wallet & Security</p><h2>Know what your account controls</h2></div><span>Security basics</span></div><div class="security-grid"><div><strong>Non-custodial wallet</strong><p>Your wallet is designed so control of the wallet credentials remains with you.</p></div><div><strong>Restrictions are different</strong><p>An account restriction can limit service access without automatically freezing the underlying blockchain assets.</p></div><div><strong>Protect your recovery phrase</strong><p>Anyone with your recovery phrase may be able to control the associated wallet. Keep it private and offline.</p></div></div></section>
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
    <div class="onboarding-actions"><button class="button button-dark" type="button" id="onboarding-next">Continue</button><form method="post" id="onboarding-complete"><input type="hidden" name="action" value="complete_onboarding"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><button class="button button-dark" type="submit">Got it — Continue to dashboard</button></form><button class="onboarding-skip" type="submit" form="onboarding-complete">Skip for now</button></div>
  </section>
</div>
<script src="/assets/dashboard.js" defer></script>
<?php endif; ?>
</body></html>
