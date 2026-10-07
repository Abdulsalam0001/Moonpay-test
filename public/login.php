<?php
require_once __DIR__.'/../src/bootstrap.php';
if(current_user()){header('Location: /dashboard.php');exit;}
$error=null;
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf($_POST['csrf_token']??null);
 $email=trim((string)($_POST['email']??''));$password=(string)($_POST['password']??'');
 if(!$email||!$password)$error='Enter your email and password.';
 elseif(login(Database::connection(),$email,$password)){header('Location: /dashboard.php');exit;}
 else $error='Unable to sign in with those credentials.';
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Sign in · MoonPay</title><link rel="stylesheet" href="/assets/app.css"></head>
<body class="auth-page"><main class="auth-card">
<div class="brand"><span class="brand-mark">M</span><span>moonpay</span></div>
<p class="eyebrow">Secure account</p><h1>Welcome back</h1><p class="muted">Sign in to manage your digital assets.</p>
<?php if($error):?><div class="alert"><?=e($error)?></div><?php endif;?>
<form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>">
<label>Email<input type="email" name="email" autocomplete="email" required></label>
<label>Password<input type="password" name="password" autocomplete="current-password" required></label>
<button class="button button-dark" type="submit">Sign in</button></form>
</main></body></html>
