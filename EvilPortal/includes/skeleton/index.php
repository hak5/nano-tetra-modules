<?php
$destination = "http://". $_SERVER['HTTP_HOST'] . $_SERVER['HTTP_URI'] . "";
?>

<HTML>
    <HEAD>
        <title>Evil Portal</title>
        <script type="text/javascript">
            function redirect() { setTimeout(function(){window.location = "/captiveportal/index.php";},100);} 
        </script>
    </HEAD>

    <BODY>
        <center>
            <h1>Evil Portal</h1>
            <p>This is the default Evil Portal page</p>

            <form method="POST" action="/captiveportal/index.php" onsubmit="redirect()">
                <input type="hidden" name="target" value="<?=$destination?>">
                <button type="submit">Authorize</button>
            </form>

        </center>

    </BODY>

</HTML>