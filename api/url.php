<?php
require_once __DIR__.'/../lib.php';
authorize_api('url');
$url=trim((string)($_GET['url']??''));
$p=parse_url($url);
$valid=is_array($p)&&isset($p['scheme'],$p['host'])&&in_array(strtolower($p['scheme']),['http','https'],true)&&filter_var($p['host'],FILTER_VALIDATE_DOMAIN,FILTER_FLAG_HOSTNAME);
json_out(['success'=>true,'api'=>APP_NAME,'data'=>['url'=>$url,'valid_format'=>$valid,'scheme'=>$p['scheme']??null,'host'=>$p['host']??null,'note'=>'Format check only; URL is not fetched or malware-scanned']]);
