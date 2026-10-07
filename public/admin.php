<?php
require_once __DIR__.'/../src/bootstrap.php';
$admin=require_admin();
$pdo=Database::connection();

if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf($_POST['csrf_token']??null);
    $action=$_POST['action']??'';
    $userId=(int)($_POST['user_id']??0);

    if($action==='edit_user'){
        $editId=(int)($_POST['edit_user_id']??0);
        $name=trim((string)($_POST['name']??''));
        $email=strtolower(trim((string)($_POST['email']??'')));
        $role=in_array($_POST['role']??'user',['user','admin'],true)?$_POST['role']:'user';
        $status=in_array($_POST['status']??'active',['active','suspended'],true)?$_POST['status']:'active';

        if($editId<=0 || $name==='' || strlen($name)>100 || !filter_var($email,FILTER_VALIDATE_EMAIL)){
            $error='Enter a valid name and email address.';
        } else {
            try{
                if($editId===(int)$admin['id']){
                    $role='admin';
                    $status='active';
                }
                $stmt=$pdo->prepare('UPDATE users SET name=:name,email=:email,role=:role,status=:status WHERE id=:id');
                $stmt->execute(['name'=>$name,'email'=>$email,'role'=>$role,'status'=>$status,'id'=>$editId]);
                header('Location: /admin.php?edited=1');
                exit;
            }catch(PDOException $e){
                $error=$e->getCode()==='23505'?'That email is already registered.':'Unable to update the account.';
            }
        }
    } elseif($action==='create_user'){
        $name=trim((string)($_POST['name']??''));
        $email=strtolower(trim((string)($_POST['email']??'')));
        $password=(string)($_POST['password']??'');
        $role=in_array($_POST['role']??'user',['user','admin'],true)?$_POST['role']:'user';

        if($name==='' || strlen($name)>100 || !filter_var($email,FILTER_VALIDATE_EMAIL)){
            $error='Enter a valid name and email address.';
        } elseif(strlen($password)<12){
            $error='Password must be at least 12 characters.';
        } else {
            try{
                $stmt=$pdo->prepare('INSERT INTO users(name,email,password_hash,role,status) VALUES(:name,:email,:hash,:role,\'active\')');
                $stmt->execute([
                    'name'=>$name,
                    'email'=>$email,
                    'hash'=>password_hash($password,PASSWORD_DEFAULT),
                    'role'=>$role
                ]);
                header('Location: /admin.php?created=1');
                exit;
            }catch(PDOException $e){
                $error=$e->getCode()==='23505'?'That email is already registered.':'Unable to create the account.';
            }
        }
    } elseif($userId === (int)$admin['id']){
        $error='You cannot change your own account status from the admin panel.';
    } elseif($userId>0 && in_array($action,['activate','suspend'],true)){
        $status=$action==='activate'?'active':'suspended';
        $stmt=$pdo->prepare('UPDATE users SET status=:status WHERE id=:id');
        $stmt->execute(['status'=>$status,'id'=>$userId]);
        header('Location: /admin.php?updated=1');
        exit;
    }
}

$users=$pdo->query('SELECT id,name,email,role,status,created_at,last_login_at FROM users ORDER BY created_at DESC')->fetchAll();
$totalUsers=count($users);
$activeUsers=count(array_filter($users,fn($u)=>$u['status']==='active'));
$admins=count(array_filter($users,fn($u)=>$u['role']==='admin'));
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin · MoonPay</title>
<link rel="stylesheet" href="/assets/app.css">
</head>
<body>
<header class="topbar">
  <a class="brand" href="/dashboard.php"><span class="brand-mark">M</span><span>moonpay</span></a>
  <nav><a href="/dashboard.php">Overview</a><a class="nav-active" href="/admin.php">Admin</a><a href="/logout.php">Log out</a></nav>
</header>
<main class="shell admin-shell">
  <div class="admin-heading">
    <div><p class="eyebrow">Administration</p><h1>Control center</h1><p class="muted">Manage account access and review user activity.</p></div>
  </div>
  <?php if(!empty($error)): ?><div class="alert"><?=e($error)?></div><?php endif; ?>
  <?php if(isset($_GET['updated'])): ?><div class="admin-success">Account status updated successfully.</div><?php endif; ?>
  <?php if(isset($_GET['created'])): ?><div class="admin-success">User account created successfully.</div><?php endif; ?>
  <?php if(isset($_GET['edited'])): ?><div class="admin-success">User details updated successfully.</div><?php endif; ?>

  <section class="admin-stats">
    <div class="admin-stat"><span>Total users</span><strong><?=$totalUsers?></strong></div>
    <div class="admin-stat"><span>Active users</span><strong><?=$activeUsers?></strong></div>
    <div class="admin-stat"><span>Administrators</span><strong><?=$admins?></strong></div>
  </section>

  <section class="panel create-user-panel">
    <div class="panel-head"><h2>Create user</h2><span>Admin only</span></div>
    <form method="post" class="create-user-form">
      <input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>">
      <input type="hidden" name="action" value="create_user">
      <label>Name<input name="name" maxlength="100" autocomplete="name" required></label>
      <label>Email<input name="email" type="email" autocomplete="email" required></label>
      <label>Password<input name="password" type="password" minlength="12" autocomplete="new-password" required></label>
      <label>Role<select name="role"><option value="user">User</option><option value="admin">Admin</option></select></label>
      <button class="button button-dark" type="submit">Create account</button>
    </form>
  </section>

  <?php if(isset($_GET['edit'])):
      $editId=(int)$_GET['edit'];
      $editStmt=$pdo->prepare('SELECT id,name,email,role,status FROM users WHERE id=:id LIMIT 1');
      $editStmt->execute(['id'=>$editId]);
      $editUser=$editStmt->fetch();
  ?>
  <?php if($editUser): ?>
  <section class="panel edit-user-panel">
    <div class="panel-head"><h2>Edit user</h2><a class="edit-cancel" href="/admin.php">Cancel</a></div>
    <form method="post" class="edit-user-form">
      <input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>">
      <input type="hidden" name="action" value="edit_user">
      <input type="hidden" name="edit_user_id" value="<?=e((string)$editUser['id'])?>">
      <div class="edit-user-grid">
        <label>Name<input name="name" maxlength="100" autocomplete="name" value="<?=e($editUser['name'])?>" required></label>
        <label>Email<input name="email" type="email" autocomplete="email" value="<?=e($editUser['email'])?>" required></label>
        <label>Role<select name="role" <?php if((int)$editUser['id']===(int)$admin['id']) echo 'disabled'; ?>><option value="user" <?=$editUser['role']==='user'?'selected':''?>>User</option><option value="admin" <?=$editUser['role']==='admin'?'selected':''?>>Admin</option></select></label>
        <label>Status<select name="status" <?php if((int)$editUser['id']===(int)$admin['id']) echo 'disabled'; ?>><option value="active" <?=$editUser['status']==='active'?'selected':''?>>Active</option><option value="suspended" <?=$editUser['status']==='suspended'?'selected':''?>>Suspended</option></select></label>
      </div>
      <p class="edit-note"><?=(int)$editUser['id']===(int)$admin['id']?'Your own role and status are locked for safety.':'You can change the name, email, role, and status.'?></p>
      <button class="button button-dark" type="submit">Save user details</button>
    </form>
  </section>
  <?php endif; ?>
  <?php endif; ?>

  <section class="panel table-wrap admin-users">
    <div class="panel-head"><h2>User accounts</h2><span><?=$totalUsers?> accounts</span></div>
    <table>
      <thead><tr><th>User</th><th>Role</th><th>Status</th><th>Created</th><th>Last login</th><th>Action</th></tr></thead>
      <tbody>
      <?php foreach($users as $u): ?>
        <tr>
          <td><strong><?=e($u['name'])?></strong><small><?=e($u['email'])?></small></td>
          <td><span class="role-badge <?=e($u['role'])?>"><?=e(ucfirst($u['role']))?></span></td>
          <td><span class="status <?=e($u['status'])?>"><?=e(ucfirst($u['status']))?></span></td>
          <td><?=e(date('M j, Y',strtotime($u['created_at'])))?></td>
          <td><?=$u['last_login_at']?e(date('M j, Y H:i',strtotime($u['last_login_at']))):'Never'?></td>
          <td>
          <?php if((int)$u['id']===(int)$admin['id']): ?>
            <span class="you-label">You</span>
          <?php else: ?>
            <div class="admin-row-actions">
              <a class="admin-edit-link" href="/admin.php?edit=<?=e((string)$u['id'])?>">Edit</a>
              <form method="post" class="admin-action-form">
              <input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>">
              <input type="hidden" name="user_id" value="<?=e((string)$u['id'])?>">
              <input type="hidden" name="action" value="<?=$u['status']==='active'?'suspend':'activate'?>">
              <button class="admin-action <?=$u['status']==='active'?'danger-action':'activate-action'?>" type="submit"><?=$u['status']==='active'?'Suspend':'Activate'?></button>
              </form>
            </div>
          <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if(!$users): ?><tr><td colspan="6" class="empty">No users found.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </section>

  <section class="admin-note">
    <div class="info-card-icon">i</div>
    <div><strong>Admin access is server-side protected</strong><p>Only users with the <code>admin</code> role can access this page. Account status changes take effect on the next authentication check.</p></div>
  </section>
</main>
</body>
</html>