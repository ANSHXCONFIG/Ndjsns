<?php
declare(strict_types=1);
require_once __DIR__.'/lib.php'; session_start();
if (!empty($_SESSION['admin_id'])) { header('Location: admin.php'); exit; }
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $q=db()->prepare('SELECT * FROM admins WHERE username=?'); $q->execute([trim((string)($_POST['username']??''))]); $u=$q->fetch();
  if ($u && password_verify((string)($_POST['password']??''),$u['password_hash'])) { session_regenerate_id(true); $_SESSION['admin_id']=$u['id']; $_SESSION['admin_user']=$u['username']; csrf_token(); header('Location: admin.php'); exit; }
  $error='Invalid username or password.';
}
?><!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin Login</title><link rel="stylesheet" href="assets/style.css"><main class="auth"><h1>ANSHXOSINT <span>MASTER</span></h1><h2>Admin login</h2><?php if($error) echo '<p class="err">'.e($error).'</p>'; ?><form method="post"><label>Username</label><input name="username" required autocomplete="username"><label>Password</label><input name="password" type="password" required autocomplete="current-password"><button>Sign in</button></form></main></html>
