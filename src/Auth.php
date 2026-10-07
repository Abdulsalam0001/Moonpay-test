<?php
declare(strict_types=1);
function current_user():?array{return $_SESSION['user']??null;}
function require_auth():array{$u=current_user();if(!$u){header('Location: /login.php');exit;}return $u;}
function require_admin():array{$u=require_auth();if(($u['role']??'')!=='admin'){http_response_code(403);exit('Forbidden.');}return $u;}
function login(PDO $pdo,string $email,string $password):bool{
 $s=$pdo->prepare('SELECT id,name,email,password_hash,role,status FROM users WHERE LOWER(email)=LOWER(:email) LIMIT 1');
 $s->execute(['email'=>trim($email)]);$u=$s->fetch();
 if(!$u||$u['status']!=='active'||!password_verify($password,$u['password_hash']))return false;
 session_regenerate_id(true);
 $_SESSION['user']=['id'=>(int)$u['id'],'name'=>$u['name'],'email'=>$u['email'],'role'=>$u['role']];
 $pdo->prepare('UPDATE users SET last_login_at=NOW() WHERE id=:id')->execute(['id'=>$u['id']]);
 return true;
}
function logout():void{$_SESSION=[];session_destroy();}
