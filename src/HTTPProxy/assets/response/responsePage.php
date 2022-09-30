<?php

    $ipaddress = '';
    if (getenv('HTTP_CLIENT_IP'))
        $ipaddress = getenv('HTTP_CLIENT_IP');
    else if(getenv('HTTP_X_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
    else if(getenv('HTTP_X_FORWARDED'))
        $ipaddress = getenv('HTTP_X_FORWARDED');
    else if(getenv('HTTP_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_FORWARDED_FOR');
    else if(getenv('HTTP_FORWARDED'))
       $ipaddress = getenv('HTTP_FORWARDED');
    else if(getenv('REMOTE_ADDR'))
        $ipaddress = getenv('REMOTE_ADDR');
    else
        $ipaddress = 'UNKNOWN';

$url="URL : ".$_SERVER['HTTP_REFERER'] ;
$string= "QUERY_STRING : " . $_SERVER['QUERY_STRING'];
$ip= "USER IP: ".$ipaddress;


//$myfile = fopen("/pineapple/modules/HTTPProxy/assets/logFile.txt", "w") or die("Unable to open file!");
//fwrite($myfile, $txt);

$txt = $url."\n". $string."\n".$ip."\n";
$file="/pineapple/modules/HTTPProxy/assets/logFile.txt";
file_put_contents($file, $txt, FILE_APPEND);



?>