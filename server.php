<?php
require_once(__DIR__.'/config/config.php');

$method = $_SERVER['REQUEST_METHOD'];
if ($method == 'POST') {
	$inputJSON = file_get_contents('php://input');
	$input = json_decode($inputJSON, true);
	require(__DIR__."/function/1a2b.php");
	require(__DIR__."/function/time.php");
	$user_id = $input['message']['chat']['id'];
	$data = @file_get_contents("data/".$user_id.".json");
	if (!$data) {
		$data=array(
			"count"=>0,
			"text"=>"",
			"start"=>($user_id > 0)
		);
	} else {
		$data = json_decode($data, true);
	}
	if (isset($input['message']['text'])) {
		$text = $input['message']['text'];
		if (($user_id > 0 && $text === "/start") || $text == '/start@oneAtwoB_bot') {
			if ($data["count"]==0) {
				$response = "已開始新遊戲！將根據輸入決定答案數字個數";
			} else {
				$response = "你猜了 ".$data["count"]." 次就放棄了，答案是".implode($data["ans"])."\n".$data["text"]."\n\n已開始新遊戲！將根據輸入決定答案數字個數";
			}
			$data=array(
				"count"=>0,
				"text"=>"",
				"start"=>true
			);
		} else if ($user_id < 0 && $text == '/stop@oneAtwoB_bot') {
			$data=array(
				"count"=>0,
				"text"=>"",
				"start"=>false
			);
			$response = "已停止遊戲";
		} else if ($data["start"]) {
			$guess = $text;
			$guess = strtr($guess, "qwertyuiop", "1234567890");
			$guesslen = strlen($guess);
			$guessarr = str_split($guess);
			if (!preg_match("/^\d{1,10}$/", $guess)) {
				if ($user_id > 0) {
					$response = "答案不符合格式，必須是1~10個不重複數字\n".$data["text"];
				}
			} else if(!checkdiff($guessarr, $guesslen)) {
				if ($user_id > 0) {
					$response = "數字不可重複！\n".$data["text"];
				}
			} else if($data["count"]!=0 && $data["len"]!=$guesslen) {
				if ($user_id > 0) {
					$response = "答案不符合目前規則，必須是".$data["len"]."個數字\n".$data["text"];
				}
			} else if(in_array($guess, $data["guess"])) {
				$response = "這個答案你猜過了！\n".$data["text"];
			} else {
				$response="";
				if ($data["count"]==0) {
					$data=array(
						"count"=> 0,
						"guess"=> array(),
						"text"=> "",
						"time"=>time(),
						"ans"=>randomans($guesslen),
						"len"=>$guesslen,
						"start"=>true
					);
					$response.="已開始 ".$data["len"]." 個數字的遊戲，欲重玩請輸入 ".($user_id>0?"/start":"/start@oneAtwoB_bot")."\n";
				}
				$data["count"]++;
				$stat=checkans($data["ans"], $guessarr, $data["len"]);
				$data["guess"][]=$guess;
				if ($data["count"] % 3 == 1) {
					$data["text"] .= "\n";
				} else {
					$data["text"] .= " | ";
				}
				$data["text"].=$guess." ".$stat[0]."A".$stat[1]."B";
				if ($stat[0]==$data["len"]) {
					$response.="你花了 ".timedifftext(time()-$data["time"])." 在 ".$data["count"]." 次猜中\n".$data["text"];
					if ($user_id > 0) {
						$response .= "\n\n已開始新遊戲！將根據輸入決定答案數字個數";
						$data=array(
							"count"=> 0,
							"text"=> "",
							"start"=>true
						);
					} else {
						$response .= "\n\n繼續玩請輸入 /start@oneAtwoB_bot";
						$data=array(
							"count"=>0,
							"text"=>"",
							"start"=>false
						);
					}
				} else {
					$response.="你已花了 ".timedifftext(time()-$data["time"])." 猜了 ".$data["count"]." 次\n".$data["text"];
				}
			}
		}
		if ($response !== "") {
			$commend = 'curl https://api.telegram.org/bot'.$cfg['token'].'/sendMessage -d "chat_id='.$user_id.'&text='.$response.'"';
			system($commend);
		}
	}
	file_put_contents("data/".$user_id.".json", json_encode($data));
}
