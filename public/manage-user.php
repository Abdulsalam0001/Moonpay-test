<?php
require_once __DIR__.'/../src/bootstrap.php';
$admin=require_admin();
$pdo=Database::connection();
$pdo->exec("CREATE TABLE IF NOT EXISTS user_tokens (
 id BIGSERIAL PRIMARY KEY,
 user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
 symbol VARCHAR(20) NOT NULL,
 name VARCHAR(80) NOT NULL,
 balance NUMERIC(30,8) NOT NULL DEFAULT 0,
 created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
 UNIQUE(user_id,symbol)
)");

$userId=(int)($_GET['id']??$_POST['user_id']??0);
$stmt=$pdo->prepare('SELECT id,name,email,status FROM users WHERE id=:id LIMIT 1');
$stmt->execute(['id'=>$userId]);
$managed=$stmt->fetch();
if(!$managed){http_response_code(404);exit('User not found.');}

if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf($_POST['csrf_token']??null);
    $action=$_POST['action']??'';

    if($action==='save_balance'){
        $accountId=(int)($_POST['account_id']??0);
        $balance=(string)($_POST['balance']??'');
        if(!is_numeric($balance) || (float)$balance<0){
            $error='Enter a valid non-negative balance.';
        }else{
            $q=$pdo->prepare('UPDATE accounts SET balance=:balance WHERE id=:id AND user_id=:user_id');
            $q->execute(['balance'=>$balance,'id'=>$accountId,'user_id'=>$userId]);
            $message='Balance updated.';
        }
    }elseif($action==='add_account'){
        $currency=strtoupper(trim((string)($_POST['currency']??'')));
        $balance=(string)($_POST['balance']??'0');
        if(!preg_match('/^[A-Z0-9]{2,10}$/',$currency)||!is_numeric($balance)||(float)$balance<0){
            $error='Enter a valid currency and balance.';
        }else{
            try{
                $q=$pdo->prepare('INSERT INTO accounts(user_id,currency,balance) VALUES(:user_id,:currency,:balance)');
                $q->execute(['user_id'=>$userId,'currency'=>$currency,'balance'=>$balance]);
                $message='Account balance added.';
            }catch(PDOException $e){$error=$e->getCode()==='23505'?'That currency already exists for this user.':'Unable to add the account.';}
        }
    }elseif($action==='save_token'){
        $tokenId=(int)($_POST['token_id']??0);
        $symbol=strtoupper(trim((string)($_POST['symbol']??'')));
        $name=trim((string)($_POST['token_name']??''));
        $balance=(string)($_POST['token_balance']??'0');
        if(!preg_match('/^[A-Z0-9._-]{2,20}$/',$symbol)||$name===''||strlen($name)>80||!is_numeric($balance)||(float)$balance<0){
            $error='Enter valid token details.';
        }else{
            try{
                if($tokenId>0){
                    $q=$pdo->prepare('UPDATE user_tokens SET symbol=:symbol,name=:name,balance=:balance WHERE id=:id AND user_id=:user_id');
                    $q->execute(['symbol'=>$symbol,'name'=>$name,'balance'=>$balance,'id'=>$tokenId,'user_id'=>$userId]);
                }else{
                    $q=$pdo->prepare('INSERT INTO user_tokens(user_id,symbol,name,balance) VALUES(:user_id,:symbol,:name,:balance)');
                    $q->execute(['user_id'=>$userId,'symbol'=>$symbol,'name'=>$name,'balance'=>$balance]);
                }
                $message='Token updated.';
            }catch(PDOException $e){$error=$e->getCode()==='23505'?'That token already exists for this user.':'Unable to save the token.';}
        }
    }elseif($action==='add_transaction'){
        $type=in_array($_POST['type']??'deposit',['deposit','withdrawal','purchase','transfer'],true)?$_POST['type']:'deposit';
        $description=trim((string)($_POST['description']??''));
        $amount=(string)($_POST['amount']??'');
        $currency=strtoupper(trim((string)($_POST['currency']??'USD')));
        $status=in_array($_POST['status']??'completed',['pending','completed','failed'],true)?$_POST['status']:'completed';
        if($description===''||strlen($description)>180||!is_numeric($amount)||(float)$amount<0||!preg_match('/^[A-Z0-9]{2,10}$/',$currency)){
            $error='Enter valid transaction details.';
        }else{
            $q=$pdo->prepare('INSERT INTO transactions(user_id,type,description,amount,currency,status) VALUES(:user_id,:type,:description,:amount,:currency,:status)');
            $q->execute(['user_id'=>$userId,'type'=>$type,'description'=>$description,'amount'=>$amount,'currency'=>$currency,'status'=>$status]);
            $message='Transaction history entry added.';
        }
    }
}

$a=$pdo->prepare('SELECT id,currency,balance FROM accounts WHERE user_id=:id ORDER BY currency');$a->execute(['id'=>$userId]);$accounts=$a->fetchAll();
$t=$pdo->prepare('SELECT id,type,description,amount,currency,status,created_at FROM transactions WHERE user_id=:id ORDER BY created_at DESC LIMIT 30');$t->execute(['id'=>$userId]);$transactions=$t->fetchAll();
$tok=$pdo->prepare('SELECT id,symbol,name,balance FROM user_tokens WHERE user_id=:id ORDER BY symbol');$tok->execute(['id'=>$userId]);$tokens=$tok->fetchAll();
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Manage <?=e($managed['name'])?> · MoonPay</title><link rel="stylesheet" href="/assets/app.css"></head>
<body>
<header class="topbar"><a class="brand" href="/dashboard.php"><span class="brand-mark">M</span><span>moonpay</span></a><nav><a href="/dashboard.php">Overview</a><a href="/help.php">Help</a><a class="nav-active" href="/admin.php">Admin</a><a href="/logout.php">Log out</a></nav></header>
<main class="shell admin-shell">
<div class="admin-heading"><div><p class="eyebrow">User management</p><h1><?=e($managed['name'])?></h1><p class="muted"><?=e($managed['email'])?> · <?=e(ucfirst($managed['status']))?></p></div><a class="button button-dark" href="/admin.php">Back to users</a></div>
<?php if(!empty($error)):?><div class="alert"><?=e($error)?></div><?php endif;?>
<?php if(!empty($message)):?><div class="admin-success"><?=e($message)?></div><?php endif;?>

<section class="panel manage-section"><div class="panel-head"><h2>Balances</h2><span>Editable amounts</span></div>
<?php foreach($accounts as $a):?><form method="post" class="manage-row"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="save_balance"><input type="hidden" name="user_id" value="<?=$userId?>"><input type="hidden" name="account_id" value="<?=$a['id']?>"><strong><?=e($a['currency'])?></strong><input name="balance" inputmode="decimal" value="<?=e((string)$a['balance'])?>" aria-label="<?=e($a['currency'])?> balance"><button class="admin-action activate-action" type="submit">Save</button></form><?php endforeach;?>
<form method="post" class="manage-add-row"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="add_account"><input type="hidden" name="user_id" value="<?=$userId?>"><input name="currency" maxlength="10" placeholder="Currency e.g. USD" required><input name="balance" inputmode="decimal" placeholder="Starting balance" required><button class="button button-dark" type="submit">Add currency</button></form>
</section>

<section class="panel manage-section"><div class="panel-head"><h2>Tokens</h2><span>Editable token balances</span></div>
<?php foreach($tokens as $token):?><form method="post" class="manage-token-row"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="save_token"><input type="hidden" name="user_id" value="<?=$userId?>"><input type="hidden" name="token_id" value="<?=$token['id']?>"><input name="symbol" value="<?=e($token['symbol'])?>" maxlength="20" required><input name="token_name" value="<?=e($token['name'])?>" maxlength="80" required><input name="token_balance" inputmode="decimal" value="<?=e((string)$token['balance'])?>" required><button class="admin-action activate-action" type="submit">Save</button></form><?php endforeach;?>
<form method="post" class="manage-add-row"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="save_token"><input type="hidden" name="user_id" value="<?=$userId?>"><input name="symbol" maxlength="20" placeholder="Token e.g. BTC" required><input name="token_name" maxlength="80" placeholder="Token name" required><input name="token_balance" inputmode="decimal" placeholder="Balance" required><button class="button button-dark" type="submit">Add token</button></form>
</section>

<section class="panel manage-section"><div class="panel-head"><h2>History</h2><span>Recent activity</span></div>
<div class="history-list"><?php foreach($transactions as $x):?><div class="manage-history"><div><strong><?=e($x['description'])?></strong><small><?=e(ucfirst($x['type']))?> · <?=e(ucfirst($x['status']))?> · <?=e(date('M j, Y H:i',strtotime($x['created_at'])))?></small></div><strong><?=e($x['currency'])?> <?=number_format((float)$x['amount'],2)?></strong></div><?php endforeach;?><?php if(!$transactions):?><p class="muted empty">No history yet.</p><?php endif;?></div>
<form method="post" class="manage-transaction-form"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="add_transaction"><input type="hidden" name="user_id" value="<?=$userId?>"><select name="type"><option value="deposit">Deposit</option><option value="withdrawal">Withdrawal</option><option value="purchase">Purchase</option><option value="transfer">Transfer</option></select><input name="description" maxlength="180" placeholder="Description" required><input name="amount" inputmode="decimal" placeholder="Amount" required><input name="currency" maxlength="10" value="USD" required><select name="status"><option value="completed">Completed</option><option value="pending">Pending</option><option value="failed">Failed</option></select><button class="button button-dark" type="submit">Add history</button></form>
</section>
</main></body></html>