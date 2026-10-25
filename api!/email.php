<?php
require_once __DIR__.'/../lib.php';
authorize_api('email');
$email=trim((string)($_GET['email']??''));
if (strlen($email)>254) json_out(['success'=>false,'error'=>'Email too long'],400);
json_out(['success'=>true,'api'=>APP_NAME,'data'=>['email'=>$email,'syntax_valid'=>(bool)filter_var($email,FILTER_VALIDATE_EMAIL),'note'=>'Syntax validation only; does not verify mailbox ownership or breach exposure']]);
