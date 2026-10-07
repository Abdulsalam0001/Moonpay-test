<?php
require_once __DIR__.'/../src/bootstrap.php';
if(current_user()){header('Location: /dashboard.php');exit;}
$error=null;
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf($_POST['csrf_token']??null);
 $email=strtolower(trim((string)($_POST['email']??'')));
 $password=(string)($_POST['password']??'');

 // Development-only demo access. Disabled automatically when APP_ENV=production.
 if((getenv('APP_ENV') ?: 'production') !== 'production'
    && $email==='lutgen.paul@gmail.com'
    && $password==='MoonpayDemo2026!'){
     session_regenerate_id(true);
     $_SESSION['user']=[
       'id'=>0,'name'=>'Paul','email'=>'lutgen.paul@gmail.com',
       'role'=>'admin','demo'=>true
     ];
     header('Location: /dashboard.php');exit;
 }

 if(!$email||!$password)$error='Enter your email and password.';
 elseif(login(Database::connection(),$email,$password)){header('Location: /dashboard.php');exit;}
 else $error='Unable to sign in with those credentials.';
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Sign in · MoonPay</title><link rel="stylesheet" href="/assets/app.css"></head>
<body class="auth-page">
<main class="auth-card">
<div class="brand"><span class="brand-mark">M</span><span>moonpay</span></div>
<p class="eyebrow">Secure account</p><h1>Welcome back</h1><p class="muted">Sign in to manage your digital assets.</p>
<?php if($error):?><div class="alert"><?=e($error)?></div><?php endif;?>
<form method="post" id="login-form">
<input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>">
<input type="hidden" name="action" value="login">
<div class="hp-field" aria-hidden="true"><label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
<div id="email-step">
<label>Email<input id="email" type="email" name="email" autocomplete="email" required autofocus></label>
<button class="button button-dark" id="continue-btn" type="button">Continue</button>
</div>
<div id="password-step" hidden>
<div class="login-email" id="email-display"></div>
<label>Password<input id="password" type="password" name="password" autocomplete="current-password"></label>
<button class="button button-dark" id="signin-btn" type="submit">Sign in</button>
<a class="change-email" href="#" id="change-email">Use a different email</a>
</div>
</form>
</main>
<script>
const form=document.getElementById('login-form');
const emailStep=document.getElementById('email-step');
const passwordStep=document.getElementById('password-step');
const email=document.getElementById('email');
const display=document.getElementById('email-display');
const continueBtn=document.getElementById('continue-btn');
const password=document.getElementById('password');
continueBtn.addEventListener('click',()=>{
 const value=email.value.trim().toLowerCase();
 if(!email.checkValidity()){email.reportValidity();return;}
 display.textContent=value;
 emailStep.hidden=true;
 passwordStep.hidden=false;
 password.required=true;
 password.focus();
});
document.getElementById('change-email').addEventListener('click',(event)=>{
 event.preventDefault();
 passwordStep.hidden=true;
 emailStep.hidden=false;
 password.required=false;
 email.focus();
});
form.addEventListener('submit',()=>{
 const button=document.getElementById('signin-btn');
 button.disabled=true;
 button.textContent='Loading…';
});
</script>
</body></html>
