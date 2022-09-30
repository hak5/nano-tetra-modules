<?php 

error_reporting(E_ALL);

if (!isset($_SERVER['argv']) && !isset($argv)) {
    echo "Please enable the register_argc_argv directive in your php.ini\n";
    exit(1);
} elseif (!isset($argv)) {
    $argv = $_SERVER['argv'];
}

if (!isset($argv[1]) || !in_array($argv[1], ['nano', 'tetra'])) {
    echo "Run with \"php opkg-parser.php [TYPE]\"\n";
    echo "    TYPE -> 'nano' or 'tetra'\n";
    exit(1);
}




echo "\nsync mk6 packages - by DSR!\n\n";

$device = $argv[1];
$moduleData = file_get_contents("https://www.wifipineapple.com/{$device}/modules");

@unlink("{$device}.json");
file_put_contents("{$device}.json", $moduleData);

$moduleDataDecode = json_decode($moduleData, true);
echo "======== Packages (" . count($moduleDataDecode) . ") ========\n";
foreach ($moduleDataDecode as $key => $value) {
    if ($value["type"] !== 'Sys') {
        echo "    [+] {$key}\n";
        $file = file_get_contents("https://www.wifipineapple.com/{$device}/modules/{$key}");
        @unlink("{$key}.tar.gz");
        file_put_contents("{$key}.tar.gz", $file);
    }
}

echo "\n\n";
echo "Complete!";
