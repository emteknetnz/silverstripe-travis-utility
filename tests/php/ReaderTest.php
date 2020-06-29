<?php

namespace Emteknetnz\TravisUtility\Tests;

use Emteknetnz\TravisUtility\Service\Config;
use Emteknetnz\TravisUtility\Service\Reader;
use PHPUnit\Framework\TestCase;

class ReaderTest extends TestCase
{
    public function testSomething(): void
    {
        $config = new Config();
        $config->setValue('dir', __DIR__ . '/fixtures/ReaderTest');
        $reader = new Reader();
        $reader->setConfig($config);
        $reader->read('a.yml');
        $this->assertEquals(5.6, $reader->getValue('phpMin'));
        $this->assertEquals(7.4, $reader->getValue('phpMax'));
        $this->assertEquals(4.4, $reader->getValue('recipeMinorMin'));
        $this->assertEquals(4.6, $reader->getValue('recipeMinorMax'));
        $this->assertEquals(4, $reader->getValue('recipeMajor'));
        $this->assertEquals(true, $reader->getValue('postgres'));
        $this->assertEquals(false, $reader->getValue('behat'));
        $this->assertEquals(true, $reader->getValue('phpcs'));
        $this->assertEquals(true, $reader->getValue('phpCoverage'));
    }
}
