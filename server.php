<?php
require_once(__DIR__.'/config/config.php');

$method = $_SERVER['REQUEST_METHOD'];
if ($method == 'POST') {
	$inputJSON = file_get_contents('php://input');
	$input = json_decode($inputJSON, true);
	require(__DIR__."/function/1a2b.php");
	require(__DIR__."/function/time.php");
	$user_id = $input['message']['chat']['id'];
	$data = file_get_contents("data/".$user_id.".json");
	if (!$data) {
		$data=array(
			"count"=> 0,
			"text"=> ""
		);
	} else {
		$data = json_decode($data, true);
	}
	if (isset($input['message']['text'])) {
		$text = $input['message']['text'];
		if ($text == '/start') {
			if ($data["count"]==0) {
				$response = "已開始新遊戲！將根據輸入決定答案數字個數";
			} else {
				$response = "你猜了 ".$data["count"]." 次就放棄了，答案是".implode($data["ans"])."\n".$data["text"]."\n\n已開始新遊戲！將根據輸入決定答案數字個數";
			}
			$data=array(
				"count"=> 0,
				"text"=> ""
			);
		} else {
			$guess = $text;
			$guess = strtr($guess, "qwertyuiop", "1234567890");
			$guesslen = strlen($guess);
			$guessarr = str_split($guess);
			if (!preg_match("/^\d{1,10}$/", $guess)) {
				$response = "答案不符合格式，必須是1~10個不重複數字\n".$data["text"];
			} else if(!checkdiff($guessarr, $guesslen)) {
				$response = "數字不可重複！\n".$data["text"];
			} else if($data["count"]!=0 && $data["len"]!=$guesslen) {
				$response = "答案不符合目前規則，必須是".$data["len"]."個數字\n".$data["text"];
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
						"len"=>$guesslen
					);
					$response.="已開始 ".$data["len"]." 個數字的遊戲，欲重玩請在輸入框左方選單選擇\n";
				}
				$data["count"]++;
				$stat=checkans($data["ans"], $guessarr, $data["len"]);
				$data["guess"][]=$guess;
				$data["text"].="\n".$guess." ".$stat[0]."A".$stat[1]."B";
				if ($stat[0]==$data["len"]) {
					$response.="你花了 ".timedifftext(time()-$data["time"])." 在 ".$data["count"]." 次猜中\n".$data["text"]."\n\n已開始新遊戲！將根據輸入決定答案數字個數";
					$data=array(
						"count"=> 0,
						"text"=> ""
					);
				} else {
					$response.="你已花了 ".timedifftext(time()-$data["time"])." 猜了 ".$data["count"]." 次\n".$data["text"];
				}
			}
		}
	}
	file_put_contents("data/".$user_id.".json", json_encode($data));
	$commend = 'curl https://api.telegram.org/bot'.$cfg['token'].'/sendMessage -d "chat_id='.$user_id.'&text='.$response.'"';
	system($commend);
}
