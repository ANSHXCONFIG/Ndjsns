<?php
require_once __DIR__.'/../lib.php';
authorize_api('ip');
$ip=trim((string)($_GET['ip']??''));
if (!filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE)) json_out(['success'=>false,'error'=>'Provide a public IP address'],400);
$data=fetch_json('https://ipwho.is/'.rawurlencode($ip));
json_out(['success'=>true,'api'=>APP_NAME,'data'=>$data]);
