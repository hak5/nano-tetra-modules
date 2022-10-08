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



//tar czf OnlineHashCrack.tar.gz OnlineHashCrack && mv OnlineHashCrack.tar.gz ../build
//tar czf PMKIDAttack.tar.gz PMKIDAttack && mv PMKIDAttack.tar.gz ../build
//tar czf Terminal.tar.gz Terminal && mv Terminal.tar.gz ../build
$extraPackages = [
    'OnlineHashCrack',
    'PMKIDAttack',
    'Terminal',
];

echo "\nsync mk6 packages - by DSR!\n\n";

$device      = $argv[1];
$disableSync = (isset($argv[2]) && filter_var($argv[2], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE));
$buildDir    = getcwd() . '/build';
$srcDir      = getcwd() . '/src';
$moduleData  = json_decode(file_get_contents("https://www.wifipineapple.com/{$device}/modules"), true);

if (!$disableSync) {
    echo "======== Packages (" . count($moduleData) . ") ========\n";
    foreach ($moduleData as $key => $value) {
        if ($value["type"] !== 'Sys') {
            echo "    [+] {$key}\n";
            $file = file_get_contents("https://www.wifipineapple.com/{$device}/modules/{$key}");
            @unlink("{$buildDir}/{$key}.tar.gz");
            file_put_contents("{$buildDir}/{$key}.tar.gz", $file);
        }
    }

    echo "\n\n";
}

echo "======== Extra Packages (" . count($extraPackages) . ") ========\n";
foreach ($extraPackages as $key) {
    echo "    [+] {$key}\n";
    $fileName = "{$buildDir}/{$key}.tar.gz";
    $infoData = json_decode(file_get_contents("{$srcDir}/{$key}/module.info"));
    
    $module = [
        'name' => $key,
        'title' => $infoData->title,
        'version' => $infoData->version,
        'description' => $infoData->description,
        'author' => $infoData->author,
        'size' => filesize($fileName),
        'checksum' => hash_file('sha256', $fileName),
        'num_downloads' => '0',
    ];
    if (isset($infoData->system)) {
        $module['type'] = 'System';
    } elseif (isset($infoData->cliOnly)) {
        $module['type'] = 'CLI';
    } else {
        $module['type'] = 'GUI';
    }

    $moduleData[ $key ] = $module;
}

asort($moduleData);
//@unlink("{$buildDir}/{$device}.json");
//file_put_contents("{$buildDir}/{$device}.json", json_encode($moduleData));
@unlink("{$buildDir}/modules.json");
file_put_contents("{$buildDir}/modules.json", json_encode($moduleData));

echo "\n\n";
echo "Complete!";
