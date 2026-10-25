<?php
require_once __DIR__.'/../lib.php';
authorize_api('rdap');
$domain=safe_domain((string)($_GET['domain']??''));
$data=fetch_json('https://rdap.org/domain/'.rawurlencode($domain));
json_out(['success'=>true,'api'=>APP_NAME,'data'=>$data]);
