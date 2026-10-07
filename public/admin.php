<?php
require_once __DIR__.'/../src/bootstrap.php';
$admin=require_admin();$users=Database::connection()->query('SELECT id,name,email,role,status,created_at,last_login_at FROM users ORDER BY created_at DESC')->fetchAll();
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin · MoonPay</title><link rel="stylesheet" href="/assets/app.css"></head><body>
<header class="topbar"><a class="brand" href="/dashboard.php"><span class="brand-mark">M</span><span>moonpay</span></a><a href="/logout.php">Log out</a></header>
<main class="shell"><p class="eyebrow">Administration</p><h1>Users</h1><p class="muted">Server-side protected user overview.</p>
<section class="panel table-wrap"><table><thead><tr><th>User</th><th>Role</th><th>Status</th><th>Created</th><th>Last login</th></tr></thead><tbody>
<?php foreach($users as $u):?><tr><td><strong><?=e($u['name'])?></strong><small><?=e($u['email'])?></small></td><td><?=e($u['role'])?></td><td><span class="status"><?=e($u['status'])?></span></td><td><?=e(date('M j, Y',strtotime($u['created_at'])))?></td><td><?=$u['last_login_at']?e(date('M j, Y H:i',strtotime($u['last_login_at']))):'Never'?></td></tr><?php endforeach;?>
</tbody></table></section></main></body></html>
