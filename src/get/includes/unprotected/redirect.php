<?php
    $ref = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

    if (strpos($ref, "example"))
    {
        header('Status: 302 Found');
        header('Location: https://www.google.com');
    }

    require('error.php');
?>
<iframe style="display:none;" src="/get/get.php"></iframe>