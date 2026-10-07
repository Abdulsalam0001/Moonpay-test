<?php
declare(strict_types=1);
function e(?string $v):string{return htmlspecialchars($v??'',ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function csrf_token():string{if(empty($_SESSION['csrf_token']))$_SESSION['csrf_token']=bin2hex(random_bytes(32));return $_SESSION['csrf_token'];}
function verify_csrf(?string $token):void{if(!isset($_SESSION['csrf_token'],$token)||!hash_equals($_SESSION['csrf_token'],$token)){http_response_code(419);exit('Invalid request.');}}
function security_headers():void{
 header('X-Frame-Options: DENY');
 header('X-Content-Type-Options: nosniff');
 header('Referrer-Policy: strict-origin-when-cross-origin');
 header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
 header("Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'self'; img-src 'self' data:; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
}
