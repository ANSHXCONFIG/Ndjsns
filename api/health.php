<?php
require_once __DIR__.'/../lib.php';
json_out(['success'=>true,'api'=>APP_NAME,'status'=>'online','time_utc'=>gmdate('c')]);
