<?php

use Emteknetnz\TravisUtility\Service\Config;
use Emteknetnz\TravisUtility\Service\Reader;
use Emteknetnz\TravisUtility\Service\Writer;

require_once __DIR__ . '/vendor/autoload.php';

$config = new Config();
$config->readConfigFile();

$reader = new Reader();
$reader->setConfig($config);

// TODO: pass in value via CLI
$subDirectory = 'silverstripe-asset-admin';

$reader->read($subDirectory);

// read options from reader, overwrite them with anything in .config (with some exceptions)
$options = [];
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

// TODO: read from git somehow
$options['composerRootVersion'] = 2.6;

$writer = new Writer($options);
$writer->write();
