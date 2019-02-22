<?php
require(__DIR__."/../config/config.php");
$commend = 'curl https://api.telegram.org/bot'.$cfg["token"].'/getWebhookinfo';
echo $commend;
system($commend);
