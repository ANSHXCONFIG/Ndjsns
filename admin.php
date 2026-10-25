<?php
declare(strict_types=1);
require_once __DIR__.'/lib.php'; session_start(); require_admin();
$pdo=db(); $notice=''; $newKey='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
 csrf_check(); $action=(string)($_POST['action']??'');
 if ($action==='create') {
   $label=trim((string)($_POST['label']??'API client')); $perms=$_POST['permissions']??[];
   $allowed=['rdap','dns','ip','email','url','phone','username','all'];
   if (!is_array($perms)) $perms=[];
   $perms=array_values(array_unique(array_intersect($allowed,$perms)));
   if (!$perms) $notice='Select at least one permission.';
   else {
    $plain=API_KEY_PREFIX.bin2hex(random_bytes(24)); $prefix=substr($plain,0,20);
    $daily=max(0,min(1000000,(int)($_POST['daily_limit']??100))); $monthly=max(0,min(10000000,(int)($_POST['monthly_limit']??2000)));
    $expiry=trim((string)($_POST['expires_at']??'')); $expiry=$expiry!==''?gmdate('Y-m-d H:i:s',strtotime($expiry.' 23:59:59 UTC')?:time()):null;
    $s=$pdo->prepare('INSERT INTO api_keys(label,key_hash,key_prefix,permissions,daily_limit,monthly_limit,expires_at,status,created_at) VALUES(?,?,?,?,?,?,?,"active",?)');
    $s->execute([$label,hash('sha256',$plain),$prefix,json_encode($perms),$daily,$monthly,$expiry,gmdate('Y-m-d H:i:s')]);
    $newKey=$plain; $notice='Key created. Copy it now; the full key is shown only once.';
   }
 } elseif ($action==='status') {
   $id=(int)($_POST['id']??0); $status=($_POST['status']??'')==='active'?'active':'disabled';
   $s=$pdo->prepare('UPDATE api_keys SET status=? WHERE id=?'); $s->execute([$status,$id]); $notice='Key status updated.';
 } elseif ($action==='delete') {
   $s=$pdo->prepare('DELETE FROM api_keys WHERE id=?'); $s->execute([(int)($_POST['id']??0)]); $notice='Key deleted.';
 }
}
$keys=$pdo->query('SELECT k.*, (SELECT COUNT(*) FROM api_logs l WHERE l.key_id=k.id) AS total_requests, (SELECT COUNT(*) FROM api_logs l WHERE l.key_id=k.id AND substr(l.created_at,1,10)="'.gmdate('Y-m-d').'") AS today_requests FROM api_keys k ORDER BY k.id DESC')->fetchAll();
$total=(int)$pdo->query('SELECT COUNT(*) FROM api_logs')->fetchColumn();
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>ANSHXOSINT Admin</title><link rel="stylesheet" href="assets/style.css"></head><body><header class="top"><div><h1>ANSHXOSINT <span>MASTER</span></h1><small>API CONTROL CENTER</small></div><nav>Signed in: <?=e((string)$_SESSION['admin_user'])?> · <a href="logout.php">Logout</a></nav></header><main class="wrap"><div class="stats"><div class="card"><small>API keys</small><strong><?=count($keys)?></strong></div><div class="card"><small>Total requests</small><strong><?=$total?></strong></div><div class="card"><small>Active keys</small><strong><?=count(array_filter($keys,fn($k)=>$k['status']==='active'))?></strong></div></div>
<?php if($notice): ?><p class="notice"><?=e($notice)?></p><?php endif; ?>
<?php if($newKey): ?><section class="card"><h2>Copy your new API key now</h2><p class="key"><?=e($newKey)?></p><p>Store securely. It cannot be shown again because only its hash is stored.</p></section><?php endif; ?>
<section class="card"><h2>Generate API key</h2><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="create"><label>Key / client label</label><input name="label" maxlength="100" placeholder="My application" required><label>Permissions</label><div class="checks"><?php foreach(['rdap','dns','ip','email','url','phone','username','all'] as $p): ?><label><input type="checkbox" name="permissions[]" value="<?=$p?>"> <?=$p?></label><?php endforeach; ?></div><div class="grid"><div><label>Daily request limit (0 = unlimited)</label><input type="number" name="daily_limit" min="0" max="1000000" value="100"></div><div><label>Monthly request limit (0 = unlimited)</label><input type="number" name="monthly_limit" min="0" max="10000000" value="2000"></div></div><label>Expiry date (optional)</label><input type="date" name="expires_at"><button>Create key</button></form></section>
<section class="card"><h2>Existing keys</h2><div class="tablewrap"><table><thead><tr><th>Label</th><th>Key prefix</th><th>Permissions</th><th>Usage today / total</th><th>Limits/day · month</th><th>Status</th><th>Actions</th></tr></thead><tbody><?php foreach($keys as $k): ?><tr><td><?=e($k['label'])?><br><small>Created <?=e($k['created_at'])?></small></td><td><code><?=e($k['key_prefix'])?>…</code></td><td><?=e(implode(', ',json_decode($k['permissions'],true)?:[]))?></td><td><?=$k['today_requests']?> / <?=$k['total_requests']?></td><td><?=$k['daily_limit']?> · <?=$k['monthly_limit']?></td><td><?=e($k['status'])?><br><small><?=e($k['expires_at']?:'No expiry')?></small></td><td><form method="post" class="inline"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="id" value="<?=$k['id']?>"><input type="hidden" name="action" value="status"><input type="hidden" name="status" value="<?=$k['status']==='active'?'disabled':'active'?>"><button class="secondary"><?=$k['status']==='active'?'Disable':'Enable'?></button></form> <form method="post" class="inline" onsubmit="return confirm('Delete this key and its logs?')"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="id" value="<?=$k['id']?>"><input type="hidden" name="action" value="delete"><button class="danger">Delete</button></form></td></tr><?php endforeach; ?><?php if(!$keys): ?><tr><td colspan="7">No API keys yet.</td></tr><?php endif; ?></tbody></table></div></section>
<section class="card"><h2>API usage examples</h2><pre>GET /api/rdap.php?domain=example.com
Authorization: Bearer YOUR_API_KEY

Phone: /api/phone.php?number=%2B919876543210
DNS:   /api/dns.php?domain=example.com
IP:    /api/ip.php?ip=8.8.8.8</pre><p>Query-string key support is available for compatibility, but Authorization: Bearer is safer because URLs can appear in logs/history.</p></section></main></body></html>
