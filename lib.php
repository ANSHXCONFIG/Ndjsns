<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $dir = dirname(DB_PATH);
    if (!is_dir($dir)) mkdir($dir, 0750, true);
    $pdo = new PDO('sqlite:' . DB_PATH, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    return $pdo;
}
function json_out(array $data, int $status=200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}
function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_check(): void {
    $got = (string)($_POST['csrf'] ?? '');
    if (!hash_equals((string)($_SESSION['csrf'] ?? ''), $got)) {
        http_response_code(400); exit('Invalid CSRF token. Please go back and retry.');
    }
}
function require_admin(): void {
    if (empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
}
function api_key_from_request(): string {
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/^Bearer\s+(.+)$/i', $header, $m)) return trim($m[1]);
    return trim((string)($_GET['key'] ?? ''));
}
function authorize_api(string $permission): array {
    $plain = api_key_from_request();
    if ($plain === '' || strlen($plain) > 200) json_out(['success'=>false,'error'=>'API key required'], 401);
    $hash = hash('sha256', $plain);
    $q = db()->prepare('SELECT * FROM api_keys WHERE key_hash=? AND status="active"');
    $q->execute([$hash]); $key = $q->fetch();
    if (!$key) json_out(['success'=>false,'error'=>'Invalid or disabled API key'], 401);
    if ($key['expires_at'] && strtotime($key['expires_at'].' UTC') < time()) json_out(['success'=>false,'error'=>'API key expired'], 403);
    $perms = json_decode($key['permissions'], true) ?: [];
    if (!in_array('all', $perms, true) && !in_array($permission, $perms, true)) json_out(['success'=>false,'error'=>'Permission denied'], 403);
    $now = gmdate('Y-m-d H:i:s');
    $day = gmdate('Y-m-d');
    $month = gmdate('Y-m');
    $pdo = db();
    $pdo->beginTransaction();
    $q = $pdo->prepare('SELECT COUNT(*) FROM api_logs WHERE key_id=? AND substr(created_at,1,10)=?');
    $q->execute([$key['id'], $day]); $daily = (int)$q->fetchColumn();
    $q = $pdo->prepare('SELECT COUNT(*) FROM api_logs WHERE key_id=? AND substr(created_at,1,7)=?');
    $q->execute([$key['id'], $month]); $monthly = (int)$q->fetchColumn();
    if ($key['daily_limit'] > 0 && $daily >= $key['daily_limit']) { $pdo->rollBack(); json_out(['success'=>false,'error'=>'Daily API limit exceeded'], 429); }
    if ($key['monthly_limit'] > 0 && $monthly >= $key['monthly_limit']) { $pdo->rollBack(); json_out(['success'=>false,'error'=>'Monthly API limit exceeded'], 429); }
    $pdo->prepare('INSERT INTO api_logs(key_id,permission,created_at,ip) VALUES(?,?,?,?)')->execute([$key['id'],$permission,$now,substr($_SERVER['REMOTE_ADDR'] ?? '',0,64)]);
    $pdo->prepare('UPDATE api_keys SET last_used_at=? WHERE id=?')->execute([$now,$key['id']]);
    $pdo->commit();
    return $key;
}
function safe_domain(string $domain): string {
    $domain = strtolower(trim($domain));
    if (strlen($domain)>253 || !preg_match('/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)(?:\.(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?))+$/', $domain)) {
        json_out(['success'=>false,'error'=>'Invalid domain'], 400);
    }
    return $domain;
}
function fetch_json(string $url): array {
    if (!function_exists('curl_init')) json_out(['success'=>false,'error'=>'PHP cURL extension is required'], 500);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER=>true, CURLOPT_FOLLOWLOCATION=>false,
        CURLOPT_CONNECTTIMEOUT=>5, CURLOPT_TIMEOUT=>12,
        CURLOPT_SSL_VERIFYPEER=>true, CURLOPT_SSL_VERIFYHOST=>2,
        CURLOPT_USERAGENT=>'ANSHXOSINT-MASTER/1.0'
    ]);
    $body = curl_exec($ch); $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    if (!is_string($body) || $status < 200 || $status >= 300) json_out(['success'=>false,'error'=>'Upstream data provider unavailable','upstream_status'=>$status], 502);
    $data = json_decode($body, true);
    if (!is_array($data)) json_out(['success'=>false,'error'=>'Invalid upstream response'], 502);
    return $data;
}
