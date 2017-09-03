<?php
function checkdiff($arr, $len) {
	for ($i=0; $i < $len-1; $i++) { 
		for ($j=$i+1; $j < $len; $j++) { 
			if ($arr[$i] == $arr[$j]) {
				return false;
			}
		}
	}
	return true;
}
function randomans($len) {
	$arr=array(0,1,2,3,4,5,6,7,8,9);
	shuffle($arr);
	return array_slice($arr, 0, $len);
}
function checkans($ans, $gue, $len) {
	$A=0;
	$B=0;
	for ($i=0; $i < $len; $i++) { 
		for ($j=0; $j < $len; $j++) {
			if ($ans[$i]==$gue[$j]) {
				if ($i==$j) $A++;
				else $B++;
			}
		}
	}
	return array($A,$B);
}
function cmp($a, $b) {
	if ($a[0] == $b[0]) {
		if ($a[1] == $b[1]) {
			return 0;
		}
		return ($a[1] < $b[1]) ? -1 : 1;
	}
	return ($a[0] < $b[0]) ? -1 : 1;
}
function generateresult($guess, $result, $column, $sort) {
	$list = [];
	foreach ($guess as $key => $value) {
		$list[$guess[$key]] = $result[$key];
	}
	if ($sort) {
		uasort($list, "cmp");
	}
	$text = "";
	$count = 0;
	foreach ($list as $key => $value) {
		if ($count % $column == 0) {
			$text .= "\n";
		} else {
			$text .= " | ";
		}
		$text .= $key." ";
		if ($value[0] == strlen($key)) {
			$text .= "BINGO!";
		} else {
			$text .= $value[0]."A".$value[1]."B";
		}
		$count++;
	}
	return $text;
}
