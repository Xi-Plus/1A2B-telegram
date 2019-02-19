<?php
require(__DIR__."/../config/config.php");
$commend = 'curl https://api.telegram.org/bot'.$cfg["token"].'/setWebhook -d "url='.urlencode($cfg["webhook"]).'&max_connections='.$cfg["max_connections"].'"';
echo $commend;
system($commend);
