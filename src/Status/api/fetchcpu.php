<?php

$MYDATA=exec("head -n 1 /proc/stat");
$MYDATE=exec("date");

echo $MYDATE."\n".$MYDATA."\n";
