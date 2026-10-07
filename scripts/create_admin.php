<?php
require_once __DIR__.'/../src/bootstrap.php';
if(PHP_SAPI!=='cli')exit("CLI only.\n");
[$script,$name,$email,$password]=$argv+[null,null,null,null];
if(!$name||!$email||!$password||strlen($password)<12)exit("Usage: php scripts/create_admin.php \"Admin Name\" admin@example.com \"StrongPassword123!\"\n");
$s=Database::connection()->prepare('INSERT INTO users(name,email,password_hash,role) VALUES(:name,:email,:hash,\'admin\')');
$s->execute(['name'=>$name,'email'=>strtolower(trim($email)),'hash'=>password_hash($password,PASSWORD_DEFAULT)]);
echo "Admin created.\n";
