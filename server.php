<?php
require_once(__DIR__.'/config/config.php');

$method = $_SERVER['REQUEST_METHOD'];
if ($method == 'POST') {
	$inputJSON = file_get_contents('php://input');
	$input = json_decode($inputJSON, true);
	require(__DIR__."/function/1a2b.php");
	require(__DIR__."/function/time.php");
	$user_id = $input['message']['chat']['id'];
	$data = @file_get_contents(__DIR__."/data/".$user_id.".json");
	if (!$data) {
		$data=array(
			"count"=>0,
			"text"=>[],
			"start"=>($user_id > 0),
			"column"=>$cfg['defalut_column'],
			"sort"=>false,
			"message_id"=>""
		);
	} else {
		$data = json_decode($data, true);
	}
	if (isset($input['message']['text'])) {
		$input = $input['message']['text'];
		$delthis = false;
		$delpre = false;
		$text = generateresult($data["guess"], $data["result"], $data["column"][$data["len"]], $data["sort"]);
		if (($user_id > 0 && $input === "/start") || $input == '/start@oneAtwoB_bot') {
			if ($data["count"]==0) {
				$response = "已開始新遊戲！將根據輸入決定答案數字個數";
				$delthis = true;
				$delpre = true;
			} else {
				$response = "你猜了 ".$data["count"]." 次就放棄了，答案是".implode($data["ans"])."\n".$text."\n已開始新遊戲！將根據輸入決定答案數字個數";
				$delpre = true;
			}
			$data["count"] = 0;
			$data["guess"] = [];
			$data["start"] = true;
		} else if ($user_id < 0 && $input == '/stop@oneAtwoB_bot') {
			$data["count"] = 0;
			$data["guess"] = [];
			$data["start"] = false;
			$data["len"] = 0;
			$response = "已停止遊戲";
		} else if (($user_id > 0 && preg_match("/^\/column( |$)/", $input)) || preg_match("/^\/column@oneAtwoB_bot( |$)/", $input)) {
			$input = preg_replace("/ {2,}/", " ", $input);
			$input = explode(" ", $input);
			$column = $input[1];
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
					$text = generateresult($data["guess"], $data["result"], $data["column"][$data["len"]], $data["sort"]);
					$response = "已將".$data["len"]."個數字的遊戲的欄位數設為".$column."\n".$text;
					if ($column > 10) {
						$response = "\n提醒：欄位數量過大";
					}
				}
			}
		} else if (($user_id > 0 && $input === "/sort") || $input == '/sort@oneAtwoB_bot') {
			$data["sort"] = !$data["sort"];
			if ($data["sort"]) {
				$response = "已開啟結果排序";
			} else {
				$response = "已關閉結果排序";
			}
		} else if ($data["start"]) {
			$guess = $input;
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
					$response.="已開始 ".$data["len"]." 個數字的遊戲，欲重玩請輸入 ".($user_id>0?"/start":"/start@oneAtwoB_bot")."\n";
				}
				$data["count"]++;
				$stat = checkans($data["ans"], $guessarr, $data["len"]);
				$data["guess"] []= $guess;
				$data["result"] []= $stat;
				$text = generateresult($data["guess"], $data["result"], $data["column"][$data["len"]], $data["sort"]);
				$text .= "\n剛剛猜測：".$guess." ".($stat[0]==$data["len"]?"BINGO!":$stat[0]."A".$stat[1]."B");
				if ($stat[0]==$data["len"]) {
					$text = generateresult($data["guess"], $data["result"], $data["column"][$data["len"]], false)."\n剛剛猜測：".$guess." BINGO!";
					$response.="你花了 ".timedifftext(time()-$data["time"])." 在 ".$data["count"]." 次猜中\n".$text;
					$data["count"] = 0;
					$data["guess"] = [];
					$data["len"] = 0;
					if ($user_id > 0) {
						$response .= "\n已開始新遊戲！將根據輸入決定答案數字個數";
					} else {
						$response .= "\n繼續玩請輸入 /start@oneAtwoB_bot";
						$data["start"] = false;
						$delpre = true;
					}
				} else {
					$response.="你已花了 ".timedifftext(time()-$data["time"])." 猜了 ".$data["count"]." 次\n".$text;
					$delpre = true;
					$delthis = true;
				}
			}
		}
		if ($response !== "") {
			$url = 'https://api.telegram.org/bot'.$cfg['token'].'/sendMessage?chat_id='.$user_id.'&text='.urlencode($response);
			$res = file_get_contents($url);
			// file_put_contents("data/".$user_id."_postlog1.txt", "del".$res);
			if ($user_id < 0) {
				$res = json_decode($res, true);
				$message_id = $res["result"]["message_id"];
				if ($delpre && $data["message_id"] !== "") {
					$url = 'https://api.telegram.org/bot'.$cfg['token'].'/deleteMessage?chat_id='.$user_id.'&message_id='.$data["message_id"];
					$res = file_get_contents($url);
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