<?php
declare(strict_types=1);

function current_user():?array{
    return $_SESSION['user']??null;
}

function require_auth():array{
    $u=current_user();
    if(!$u){
        header('Location: /login.php');
        exit;
    }

    $last=(int)($_SESSION['last_activity']??time());
    if(time()-$last>1800){
        logout();
        header('Location: /login.php?expired=1');
        exit;
    }

    $_SESSION['last_activity']=time();
    return $u;
}

function require_admin():array{
    $u=require_auth();
    if(($u['role']??'')!=='admin'){
        http_response_code(403);
        exit('Forbidden.');
    }
    return $u;
}

function client_ip():string{
    return $_SERVER['REMOTE_ADDR']??'0.0.0.0';
}

function login(PDO $pdo,string $email,string $password):bool{
    $email=strtolower(trim($email));
    $ipHash=hash('sha256',client_ip());

    $guard=$pdo->prepare(
        'SELECT COUNT(*) FROM login_attempts
         WHERE (email=:email OR ip_hash=:ip)
         AND attempted_at > NOW() - INTERVAL \'15 minutes\'
         AND successful=false'
    );
    $guard->execute(['email'=>$email,'ip'=>$ipHash]);
    if((int)$guard->fetchColumn()>=8)return false;

    $s=$pdo->prepare(
        'SELECT id,name,email,password_hash,role,status
         FROM users
         WHERE LOWER(email)=LOWER(:email)
         LIMIT 1'
    );
    $s->execute(['email'=>$email]);
    $u=$s->fetch();

    $valid=$u && $u['status']==='active' && password_verify($password,$u['password_hash']);

    $attempt=$pdo->prepare(
        'INSERT INTO login_attempts(email,ip_hash,successful)
         VALUES(:email,:ip,:success)'
    );
    $attempt->execute([
        'email'=>$email,
        'ip'=>$ipHash,
        'success'=>$valid?'true':'false'
    ]);

    if(!$valid)return false;

    session_regenerate_id(true);
    $_SESSION['user']=[
        'id'=>(int)$u['id'],
        'name'=>$u['name'],
        'email'=>$u['email'],
        'role'=>$u['role']
    ];
    $_SESSION['last_activity']=time();

    $pdo->prepare('UPDATE users SET last_login_at=NOW() WHERE id=:id')
        ->execute(['id'=>$u['id']]);

    return true;
}

function logout():void{
    $_SESSION=[];
    if(ini_get('session.use_cookies')){
        $params=session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time()-42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
}
