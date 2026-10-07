<?php
require_once __DIR__.'/../src/bootstrap.php';
header('Location: '.(current_user()?'/dashboard.php':'/login.php'));
exit;
