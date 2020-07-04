<?php

// helper util to scan travis files

use Emteknetnz\TravisUtility\Service\Config;

require_once __DIR__ . '/vendor/autoload.php';

$config = new Config();
$config->readConfigFile();
$dir = $config->getValue('dir');

function getModulesToBehats($dir) {
    foreach (glob("$dir/**/.travis.yml") as $path) {
        preg_match('@/([a-zA-Z0-9\-_]+)/\.travis\.yml@', $path, $m);
        $subDirectory = $m[1];
        $s = file_get_contents($path);
        preg_match_all('/(@[a-z\-]+)/', $s, $m);
        foreach ($m[1] as $behat) {
            echo "'$subDirectory' => ['$behat'],\n";
        }
    }
}

function getModulesWithBehat($dir) {
    foreach (glob("$dir/**/.travis.yml") as $path) {
        preg_match('@/([a-zA-Z0-9\-_]+)/\.travis\.yml@', $path, $m);
        $subDirectory = $m[1];
        $s = file_get_contents($path);
        if (!preg_match('/BEHAT_TEST/', $s)) {
            continue;
        }
        echo "$subDirectory\n";
    }
}

getModulesToBehats($dir);
//getModulesWithBehat($dir);