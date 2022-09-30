<?php

header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 30 Sep 1998 03:13:37");


$loop = 0;
foreach(glob("/www/Rolls/*/index.php") as $roll){
	$rolls[$loop] = $roll;
	$loop++;
}

$element = rand(0, count($rolls)-1);

require($rolls[$element]);
