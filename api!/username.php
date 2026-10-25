<?php
require_once __DIR__.'/../lib.php';
authorize_api('username');
$user=trim((string)($_GET['username']??''));
if (!preg_match('/^[A-Za-z0-9._-]{1,50}$/',$user)) json_out(['success'=>false,'error'=>'Invalid username format'],400);
json_out(['success'=>true,'api'=>APP_NAME,'data'=>['username'=>$user,'public_search_url'=>'https://www.google.com/search?q='.rawurlencode('"'.$user.'"'),'note'=>'Returns a public web search link; it does not enumerate or access private accounts.']]);
