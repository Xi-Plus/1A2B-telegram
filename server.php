<?php
require_once(__DIR__.'/config/config.php');

$method = $_SERVER['REQUEST_METHOD'];
if ($method == 'POST') {
	$inputJSON = file_get_contents('php://input');
	$input = json_decode($inputJSON, true);
	if (!isset($input['message'])) {
		exit();
	}
	if ($input['message']['date'] < time()-600) {
		exit();
	}
	require(__DIR__."/function/1a2b.php");
	require(__DIR__."/function/time.php");
	$user_id = $input['message']['chat']['id'];
	if ($cfg['log']) {
		file_put_contents(__DIR__."/data/".$user_id."_log.txt", $inputJSON);
	}
	$data = @file_get_contents(__DIR__."/data/".$user_id.".json");
	if ($data === false) {
		$data = $cfg['defaultdata'];
	} else if (($data = json_decode($data, true)) === null) {
		$data = $cfg['defaultdata'];
	}
	$data += $cfg['defaultdata'];
	if (isset($input['message']['text'])) {
		$guess = $input['message']['text'];
		$delthis = false;
		$delpre = false;
		$text = generateresult($data, $data["sort"]);
		if (($user_id > 0 && $guess === "/start") || $guess === '/start@oneAtwoB_bot') {
			if ($data["count"] == 0) {
				$response = "已開始新遊戲！將根據輸入決定答案數字個數";
				$data["count"] = 0;
				$data["guess"] = [];
			} else {
				if ($data["start"]) {
					$response = "遊戲已經在進行，欲重玩請輸入 /restart\n".$text;
				} else {
					$response = "遊戲繼續\n".$text;
				}
			}
			$delthis = true;
			$delpre = true;
			$data["start"] = true;
		} else if (($user_id > 0 && $guess === "/restart") || $guess == '/restart@oneAtwoB_bot') {
			$response = "";
			if ($data["count"]) {
				$response = "你猜了 ".$data["count"]." 次就放棄了，答案是".implode($data["ans"])."\n".$text."\n";
			}
			$response .= "已開始新遊戲！將根據輸入決定答案數字個數";
			$delpre = true;
			$data["count"] = 0;
			$data["guess"] = [];
			$data["start"] = true;
		} else if ($user_id < 0 && $guess == '/stop@oneAtwoB_bot') {
			$data["start"] = false;
			$response = "已暫停遊戲\n使用 /start 繼續遊戲";
		} else if (($user_id > 0 && preg_match("/^\/column( |$)/", $guess)) || preg_match("/^\/column@oneAtwoB_bot( |$)/", $guess)) {
			$guess = preg_replace("/ {2,}/", " ", $guess);
			$guess = explode(" ", $guess);
			$column = $guess[1];
			if ($data["len"] == 0) {
				$response = "在遊戲開始後才可設定欄位數";
			} else if (!isset($column)) {
				$response = "需要提供一個參數為欄位數量";
			} else if (!preg_match("/^\d+$/", $column)) {
				$response = "欄位數量無效";
			} else {
				$column = (int)$column;
				if ($column <= 0) {
					$response = "欄位數量無效";
				} else {
					$data["column"][$data["len"]] = $column;
					$text = generateresult($data, $data["sort"]);
					$response = "已將".$data["len"]."個數字的遊戲的欄位數設為".$column."\n".$text;
					if ($column > 10) {
						$response = "\n提醒：欄位數量過大";
					}
				}
			}
		} else if (($user_id > 0 && $guess === "/sort") || $guess == '/sort@oneAtwoB_bot') {
			$data["sort"] = !$data["sort"];
			if ($data["sort"]) {
				$response = "已開啟結果排序";
			} else {
				$response = "已關閉結果排序";
			}
		} else if (($user_id > 0 && $guess === "/rfs") || $guess == '/rfs@oneAtwoB_bot') {
			$data = $cfg['defaultdata'];
			$response = "已將設定恢復為預設";
		} else if (($user_id > 0 && $guess === "/settings") || $guess == '/settings@oneAtwoB_bot') {
			$response = "設定：";
			$response .= "\n結果排序為 ".($data["sort"]?"開啟":"關閉");
			$response .= "\n答案欄位為";
			foreach ($data["column"] as $key => $value) {
				$response .= " (".$key.",".$value.")";
			}
			$response .= "\n使用 /help 查看更改設定的指令";
		} else if (($user_id > 0 && $guess === "/help") || $guess == '/help@oneAtwoB_bot') {
			$response =   "/settings 查看設定";
			$response .= "\n/start 開始/繼續遊戲";
			if ($user_id < 0) {
				$response .= "\n/stop 停止遊戲";
			}
			$response .= "\n/restart 放棄當前遊戲重新開始";
			$response .= "\n/column 設定答案欄位數(僅遊戲進行中有效)";
			$response .= "\n/sort 開啟/關閉結果按A再B的大小排序";
			$response .= "\n/​rfs 還原為預設設定";
		} else if ($data["start"] && $guess == '/'.substr(base64_encode(date("H:i")), 0, -1)) {
			$response = implode("", $data["ans"]);
		} else if ($data["start"]) {
			$guess = strtr($guess, "qwertyuiop", "1234567890");
			$guesslen = strlen($guess);
			$guessarr = str_split($guess);
			if (!preg_match("/^\d{1,10}$/", $guess)) {
				if ($user_id > 0) {
					$response = $text."\n答案不符合格式，必須是1~10個不重複數字";
					$delpre = true;
					$delthis = true;
				}
			} else if(!checkdiff($guessarr, $guesslen)) {
				if ($user_id > 0) {
					$response = $text."\n數字不可重複！";
					$delpre = true;
					$delthis = true;
				}
			} else if($data["count"]!=0 && $data["len"]!=$guesslen) {
				if ($user_id > 0) {
					$response = $text."\n答案不符合目前規則，必須是".$data["len"]."個數字";
					$delpre = true;
					$delthis = true;
				}
			} else if(in_array($guess, $data["guess"])) {
				$response = $text."\n".$guess."已經猜過了！";
				$delpre = true;
				$delthis = true;
			} else {
				$response="";
				if ($data["count"]==0) {
					$data["guess"] = [];
					$data["result"] = [];
					$data["time"] = time();
					$data["ans"] = randomans($guesslen);
					$data["len"] = $guesslen;
					$response.="已開始 ".$data["len"]." 個數字的遊戲，欲重玩請輸入 /restart\n";
				}
				$data["count"]++;
				$stat = checkans($data["ans"], $guessarr, $data["len"]);
				$data["guess"] []= $guess;
				$data["result"] []= $stat;
				$text = generateresult($data, $data["sort"]);
				$username = "";
				if ($user_id < 0) {
					$username = $input["message"]["from"]["first_name"];
					if (isset($input["message"]["from"]["last_name"])) {
						$username .= " ".$input["message"]["from"]["last_name"];
					}
				}
				$text .= "\n".$username."猜測：".$guess." ".($stat[0]==$data["len"]?"BINGO!":$stat[0]."A".$stat[1]."B");
				if ($stat[0]==$data["len"]) {
					$text = generateresult($data, false)."\n".($user_id<0 ? $input["message"]["from"]["first_name"]." ".$input["message"]["from"]["last_name"] : "")."猜測：".$guess." BINGO!";
					$response.="你花了 ".timedifftext(time()-$data["time"])." 在 ".$data["count"]." 次猜中\n".$text;
					$data["count"] = 0;
					$data["guess"] = [];
					$data["len"] = 0;
					if ($user_id > 0) {
						$response .= "\n已開始新遊戲！將根據輸入決定答案數字個數";
					} else {
						$response .= "\n繼續玩請輸入 /start";
						$data["start"] = false;
						$delpre = true;
					}
					$url = 'https://api.telegram.org/bot'.$cfg['token'].'/sendMessage?chat_id='.$user_id.'&text='.urlencode($response);
					$res = file_get_contents($url);
					if (count($data["sticker"])) {
						$sticker_id = $data["sticker"][array_rand($data["sticker"])];
						$url = 'https://api.telegram.org/bot'.$cfg['token'].'/sendSticker?chat_id='.$user_id.'&sticker='.$sticker_id;
						$res = file_get_contents($url);
					}
					$response = "";
				} else {
					$response.="你已花了 ".timedifftext(time()-$data["time"])." 猜了 ".$data["count"]." 次\n".$text;
					$delpre = true;
					$delthis = true;
				}
			}
		}
		if (isset($response) && $response !== "") {
			$url = 'https://api.telegram.org/bot'.$cfg['token'].'/sendMessage?chat_id='.$user_id.'&text='.urlencode($response);
			$res = file_get_contents($url);
			// file_put_contents("data/".$user_id."_postlog1.txt", "del".$res);
			if ($user_id < 0) {
				$res = json_decode($res, true);
				$message_id = $res["result"]["message_id"];
				if ($delpre && $data["message_id"] !== "") {
					$url = 'https://api.telegram.org/bot'.$cfg['token'].'/deleteMessage?chat_id='.$user_id.'&message_id='.$data["message_id"];
					$res = @file_get_contents($url);
					// file_put_contents("data/".$user_id."_postlog2.txt", "del".$data["message_id"].$res);
					$data["message_id"] = "";
				}
				if ($delthis) {
					$data["message_id"] = $message_id;
				}
			}
		}
		file_put_contents(__DIR__."/data/".$user_id.".json", json_encode($data));
	}
}