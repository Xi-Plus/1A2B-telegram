<?php
function timediffformat($time) {
	$res = array();
	$res[0] = floor($time / (60 * 60 * 24));
	$time -= $res[0] * (60 * 60 * 24);
	$res[1] = floor($time / (60 * 60));
	$time -= $res[1] * (60 * 60);
	$res[2] = floor($time / (60));
	$time -= $res[2] * (60);
	$res[3] = $time;
	return $res;
}
function timedifftext($time) {
	$arr = timediffformat($time);
	$res = "";
	if ($arr[0] > 0) {
		$res .= $arr[0] . "天";
	}
	if ($arr[0] + $arr[1] > 0) {
		$res .= $arr[1] . "小時";
	}
	if ($arr[0] + $arr[1] + $arr[2] > 0) {
		$res .= $arr[2] . "分";
	}
	$res .= $arr[3] . "秒";
	return $res;
}
