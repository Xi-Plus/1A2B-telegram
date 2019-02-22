<?php
require __DIR__ . "/../config/config.php";

$action = $argv[1] ?? "get";

switch ($action) {
	case 'set':
		$commend = 'curl https://api.telegram.org/bot' . $cfg["token"] . '/setWebhook -d "url=' . urlencode($cfg["webhook"]) . '&max_connections=' . $cfg["max_connections"] . '"';
		break;

	case 'delete':
		$commend = 'curl https://api.telegram.org/bot' . $cfg["token"] . '/deleteWebhook';
		break;

	case 'get':
		$commend = 'curl https://api.telegram.org/bot' . $cfg["token"] . '/getWebhookinfo';
		break;

	default:
		exit("Unknown action.");
}

echo $commend;
system($commend);
