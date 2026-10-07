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
$tokens=[];
if(empty($user['demo'])){
    $tokenStmt=Database::connection()->prepare('SELECT symbol,name,balance FROM user_tokens WHERE user_id=:id ORDER BY symbol');
    $tokenStmt->execute(['id'=>$user['id']]);
    $tokens=$tokenStmt->fetchAll();
}
$total=0;foreach($accounts as $x)if($x['currency']==='USD')$total+=(float)$x['balance'];
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Overview · MoonPay</title><link rel="stylesheet" href="/assets/app.css"></head>
<body>
<header class="topbar">
  <a class="brand" href="/dashboard.php"><span class="brand-mark">M</span><span>moonpay</span></a>
  <nav><a class="nav-active" href="/dashboard.php">Overview</a><a href="/help.php">Help</a><?php if(($user['role']??'')==='admin' && empty($user['demo'])):?><a href="/admin.php">Admin</a><?php endif;?><a href="/logout.php">Log out</a></nav>
</header>
<main class="shell financial-shell">
  <div class="hero-row"><div><p class="eyebrow">Overview</p><h1>Good to see you, <?=e($user['name'])?>.</h1><p class="muted">Here is your account overview and recent financial activity.</p></div></div>
  <?php if(!empty($user['demo'])):?><div class="demo-notice"><div><strong>Demo environment</strong><span>This account is simulated and does not connect to a live blockchain.</span></div><span class="restriction-pill">Mainnet access restricted</span></div><?php endif;?>
  <section class="balance-card financial-balance"><div><span>Total USD balance</span><small class="balance-label">Available balance</small></div><strong>$<?=number_format($total,2)?></strong><div class="balance-meta"><span><?=!empty($user['demo'])?'Demo portfolio':'Portfolio'?></span><span><?=!empty($user['demo'])?'No mainnet access':'Account balance'?></span></div></section>
  <section class="quick-actions">
    <a href="/account-action.php?action=buy" class="quick-action"><span>＋</span><strong>Buy crypto</strong><small>Purchase assets</small></a>
    <a href="/account-action.php?action=send" class="quick-action"><span>↗</span><strong>Send</strong><small>Transfer assets</small></a>
    <a href="/account-action.php?action=receive" class="quick-action"><span>↓</span><strong>Receive</strong><small>View deposit details</small></a>
    <a href="/help.php" class="quick-action"><span>?</span><strong>Get help</strong><small>Wallet & account guidance</small></a>
  </section>
  <?php if(!empty($user['demo'])):?><section class="restricted-card"><div class="restricted-icon">!</div><div><p class="eyebrow">Token access</p><h2>Mainnet access is restricted</h2><p class="muted">The balances shown here are simulated. Mainnet transfers, withdrawals, and blockchain transactions are unavailable.</p></div></section><?php endif;?>
  <div class="grid-2 financial-grid">
    <section class="panel"><div class="panel-head"><h2>Accounts</h2><span><?=count($accounts)?> currencies</span></div><?php foreach($accounts as $x):?><div class="asset-row"><div class="asset-icon"><?=e(substr($x['currency'],0,1))?></div><div class="asset-copy"><strong><?=e($x['currency'])?></strong><small>Available balance</small></div><strong><?=number_format((float)$x['balance'],2)?></strong></div><?php endforeach;?><?php if(!$accounts):?><p class="muted empty">No balances yet.</p><?php endif;?></section>
    <section class="panel"><div class="panel-head"><h2>Recent activity</h2><span>Latest</span></div><?php foreach($transactions as $x):?><div class="transaction-row"><div><strong><?=e($x['description'])?></strong><small><?=e(ucfirst($x['status']))?> · <?=e(date('M j, Y',strtotime($x['created_at'])))?></small></div><strong class="<?=$x['type']==='withdrawal'?'negative':'positive'?>"><?=$x['type']==='withdrawal'?'-':'+'?><?=e($x['currency'])?> <?=number_format((float)$x['amount'],2)?></strong></div><?php endforeach;?><?php if(!$transactions):?><p class="muted empty">No transactions yet.</p><?php endif;?></section>
  </div>
  <section class="panel" style="margin-top:22px"><div class="panel-head"><h2>Tokens</h2><span><?=count($tokens)?> assets</span></div><?php foreach($tokens as $token):?><div class="asset-row"><div class="asset-icon"><?=e(substr($token['symbol'],0,1))?></div><div class="asset-copy"><strong><?=e($token['symbol'])?></strong><small><?=e($token['name'])?></small></div><strong><?=rtrim(rtrim(number_format((float)$token['balance'],8,'.',''),'0'),'.')?></strong></div><?php endforeach;?><?php if(!$tokens):?><p class="muted empty">No token balances yet.</p><?php endif;?></section>
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
