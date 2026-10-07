<?php
require_once __DIR__.'/../src/bootstrap.php';
$admin=require_admin();
$pdo=Database::connection();

if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf($_POST['csrf_token']??null);
    $action=$_POST['action']??'';
    $userId=(int)($_POST['user_id']??0);

    if($userId === (int)$admin['id']){
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

  <section class="admin-stats">
    <div class="admin-stat"><span>Total users</span><strong><?=$totalUsers?></strong></div>
    <div class="admin-stat"><span>Active users</span><strong><?=$activeUsers?></strong></div>
    <div class="admin-stat"><span>Administrators</span><strong><?=$admins?></strong></div>
  </section>

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
            <form method="post" class="admin-action-form">
              <input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>">
              <input type="hidden" name="user_id" value="<?=e((string)$u['id'])?>">
              <input type="hidden" name="action" value="<?=$u['status']==='active'?'suspend':'activate'?>">
              <button class="admin-action <?=$u['status']==='active'?'danger-action':'activate-action'?>" type="submit"><?=$u['status']==='active'?'Suspend':'Activate'?></button>
            </form>
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