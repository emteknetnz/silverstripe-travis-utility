<?php

use Emteknetnz\TravisUtility\Service\Config;
use Emteknetnz\TravisUtility\Service\Reader;
use Emteknetnz\TravisUtility\Service\Writer;

$config = new Config();
$config->readConfigFile();

$reader = new Reader();
$reader->setConfig($config);
$reader->read('silverstripe-auditor/.travis.yml'); // TODO: pass in value via CLI

// default options
$keys = [
    'behat',
    'coreModule',
    'npm',
    'pdo',
    'phpcs',
    'phpCoverage',
    'phpMin',
    'phpMax',
    'postgres',
    'recipeMinorMin',
    'recipeMinorMax',
    'recipeMajor'
];

$writer = new Writer();
