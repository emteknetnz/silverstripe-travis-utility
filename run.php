<?php

use Emteknetnz\TravisUtility\Service\Config;
use Emteknetnz\TravisUtility\Service\Reader;
use Emteknetnz\TravisUtility\Service\Writer;

require_once __DIR__ . '/vendor/autoload.php';

$config = new Config();
$config->readConfigFile();

$reader = new Reader($config);

// use current working directory to work out what intention is
$cwd = realpath(getcwd());
$doingDevelopment = $cwd == realpath(__DIR__);

if ($doingDevelopment) {
    $subPath = 'silverstripe-asset-admin';
    $gitBranch = 4.6;
    $config->setValue('dir', $config->getValue('developmentDataDir'));
} else {
    // using program to do actual work, e.g.
    // cd ~/Modules/silverstripe-asset-admin
    // php ../../silverstripe-travis-utility/run.php
    // => /Users/myuser/Modules/silverstripe-asset-admin
    $subPath = preg_match('@/([^/]+)$@', $cwd, $m);
    $subPath = $m[1];
    $gitBranch = shell_exec('git rev-parse --abbrev-ref HEAD');
    if (preg_match('@pulls/([^/]+)/.+@', $gitBranch, $m)) {
        $gitBranch = $m[1];
    }
    $config->setValue('dir', dirname($cwd));
}

// read options from reader, overwrite them with anything in .config (with some exceptions)
$options = [
    'subPath' => $subPath,
    'composerRootVersion' => $gitBranch
];
$reader->read($subPath);
foreach (Writer::OPTION_KEYS as $key) {
    // reader
    $readerValue = $reader->getValue($key);
    if (!is_null($readerValue)) {
        $options[$key] = $readerValue;
    }
    // config
    $configValue = $config->getValue($key);
    if (!is_null($configValue)) {
        // use reader value over config value for min php and recipe versions
        if (in_array($key, ['phpMin', 'recipeMinorMin']) &&
            !is_null($readerValue) &&
            $configValue < $readerValue
        ) {
            continue;
        }
        $options[$key] = $configValue;
    }
}

// write output
$writer = new Writer($options);
if ($doingDevelopment) {
    $writer->writeToDevelopmentOutput();
} else {
    $writer->writeToTravisFile();
}
