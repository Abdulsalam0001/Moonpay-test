<?php
declare(strict_types=1);

final class Database {
 private static ?PDO $pdo=null;
 public static function connection(): PDO {
  if(self::$pdo) return self::$pdo;
  $host=getenv('DB_HOST')?:'';$port=getenv('DB_PORT')?:'5432';$name=getenv('DB_NAME')?:'';$user=getenv('DB_USER')?:'';$password=getenv('DB_PASSWORD')?:'';$ssl=getenv('DB_SSLMODE')?:'require';
  if(!$host||!$name||!$user) throw new RuntimeException('Database configuration is incomplete.');
  self::$pdo=new PDO("pgsql:host=$host;port=$port;dbname=$name;sslmode=$ssl",$user,$password,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
  return self::$pdo;
 }
}
