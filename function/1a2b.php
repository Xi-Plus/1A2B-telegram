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
function generateresult($guess, $result, $column) {
	$text = "";
	foreach ($guess as $key => $value) {
		if ($key % $column == 0) {
			$text .= "\n";
		} else {
			$text .= " | ";
		}
		$text .= $guess[$key]." ".$result[$key];
	}
	return $text;
}
