<?php
declare(strict_types=1);
require_once __DIR__.'/Security.php';require_once __DIR__.'/Database.php';require_once __DIR__.'/Auth.php';
function load_env_file(string $path):void{
 if(!is_readable($path))return;
 foreach(file($path,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $line){
  $line=trim($line);if(!$line||str_starts_with($line,'#')||!str_contains($line,'='))continue;
  [$k,$v]=explode('=',$line,2);if(getenv(trim($k))===false)putenv(trim($k).'='.trim($v));
 }
}
load_env_file(dirname(__DIR__).'/.env');
$secure=filter_var(getenv('SESSION_SECURE')?:'false',FILTER_VALIDATE_BOOLEAN);
session_name(getenv('SESSION_NAME')?:'moonpay_test');
session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>$secure,'httponly'=>true,'samesite'=>'Lax']);
session_start();security_headers();
