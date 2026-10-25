<?php
require_once __DIR__.'/../lib.php';
authorize_api('dns');
$domain=safe_domain((string)($_GET['domain']??''));
$type=strtoupper((string)($_GET['type']??'A'));
if (!in_array($type,['A','AAAA','MX','TXT','NS','CNAME','SOA'],true)) json_out(['success'=>false,'error'=>'Unsupported DNS type'],400);
$data=fetch_json('https://dns.google/resolve?name='.rawurlencode($domain).'&type='.$type);
json_out(['success'=>true,'api'=>APP_NAME,'data'=>$data]);
