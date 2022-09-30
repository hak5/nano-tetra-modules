<?php

$MYDATA=exec("grep ".$_GET['if']." /proc/net/dev | tr -s ' ' ' '");
$MYDATE=exec("date");

echo $MYDATE."\n".$MYDATA."\n";
