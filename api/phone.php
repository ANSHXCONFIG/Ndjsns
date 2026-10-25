<?php
require_once __DIR__.'/../lib.php';
authorize_api('phone');
$number=trim((string)($_GET['number']??''));
if (!preg_match('/^\+?[0-9 ()-]{7,25}$/',$number)) json_out(['success'=>false,'error'=>'Invalid phone number format'],400);
$digits=preg_replace('/\D/','',$number);
$country=null;$dial=null;
if (str_starts_with($number,'+91') || (strlen($digits)===10 && !str_starts_with($number,'+'))) {$country='India';$dial='+91';}
elseif (str_starts_with($number,'+1')) {$country='United States/Canada';$dial='+1';}
elseif (str_starts_with($number,'+44')) {$country='United Kingdom';$dial='+44';}
elseif (str_starts_with($number,'+61')) {$country='Australia';$dial='+61';}
json_out(['success'=>true,'api'=>APP_NAME,'data'=>['input'=>$number,'normalized_digits'=>$digits,'country_guess'=>$country,'dial_code_guess'=>$dial,'format_accepted'=>true,'carrier'=>null,'region'=>null,'note'=>'Basic format and dialing-code hints only. No subscriber identity, live location, private records or verified carrier data.']]);
