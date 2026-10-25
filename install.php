<?php
declare(strict_types=1);
require_once __DIR__.'/lib.php';
session_start();
$pdo = db();
$pdo->exec('CREATE TABLE IF NOT EXISTS admins(id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT UNIQUE NOT NULL, password_hash TEXT NOT NULL, created_at TEXT NOT NULL)');
$pdo->exec('CREATE TABLE IF NOT EXISTS api_keys(id INTEGER PRIMARY KEY AUTOINCREMENT, label TEXT NOT NULL, key_hash TEXT UNIQUE NOT NULL, key_prefix TEXT NOT NULL, permissions TEXT NOT NULL, daily_limit INTEGER NOT NULL DEFAULT 100, monthly_limit INTEGER NOT NULL DEFAULT 2000, expires_at TEXT NULL, status TEXT NOT NULL DEFAULT "active", created_at TEXT NOT NULL, last_used_at TEXT NULL)');
$pdo->exec('CREATE TABLE IF NOT EXISTS api_logs(id INTEGER PRIMARY KEY AUTOINCREMENT, key_id INTEGER NOT NULL, permission TEXT NOT NULL, created_at TEXT NOT NULL, ip TEXT, FOREIGN KEY(key_id) REFERENCES api_keys(id) ON DELETE CASCADE)');
$pdo->exec('CREATE INDEX IF NOT EXISTS idx_logs_key_date ON api_logs(key_id, created_at)');
if ((int)$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0) { exit('Setup already completed. Delete or disable install.php now.'); }
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!hash_equals(ADMIN_SETUP_TOKEN, (string)($_POST['setup_token'] ?? '')) || ADMIN_SETUP_TOKEN==='CHANGE_THIS_SETUP_TOKEN_NOW') $error='Set ADMIN_SETUP_TOKEN in hosting environment to a long random secret, then enter it here.';
  elseif (strlen((string)($_POST['password'] ?? '')) < 12) $error='Password must be at least 12 characters.';
  else {
    $username=trim((string)($_POST['username'] ?? 'admin'));
    if (!preg_match('/^[a-zA-Z0-9_.-]{3,40}$/',$username)) $error='Username must be 3–40 letters, numbers, dot, dash or underscore.';
    else { $s=$pdo->prepare('INSERT INTO admins(username,password_hash,created_at) VALUES(?,?,?)'); $s->execute([$username,password_hash((string)$_POST['password'],PASSWORD_DEFAULT),gmdate('Y-m-d H:i:s')]); header('Location: login.php'); exit; }
  }
}
?><!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>ANSHXOSINT Setup</title><style>body{background:#07110f;color:#eafff6;font:16px Arial;max-width:520px;margin:8vh auto;padding:20px}form{background:#10201b;padding:24px;border-radius:16px}input{box-sizing:border-box;width:100%;padding:12px;margin:8px 0 16px;background:#07110f;color:white;border:1px solid #2d6552;border-radius:8px}button{padding:12px 18px;background:#36e6a0;border:0;border-radius:8px;font-weight:bold}</style><h1>ANSHXOSINT MASTER</h1><h2>First-time admin setup</h2><?php if($error) echo '<p>'.e($error).'</p>'; ?><form method="post"><label>Setup token</label><input name="setup_token" required><label>Admin username</label><input name="username" value="admin" required><label>Admin password (12+ characters)</label><input name="password" type="password" minlength="12" required><button>Create admin</button></form><p>After setup, remove install.php or block it at server level.</p></html>
