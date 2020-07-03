<?php

namespace Emteknetnz\TravisUtility\Tests;

use Emteknetnz\TravisUtility\Service\Config;
use Emteknetnz\TravisUtility\Service\Reader;
use PHPUnit\Framework\TestCase;

class ReaderTest extends TestCase
{
    public function testA(): void
    {
        $config = new Config();
        $config->setValue('dir', __DIR__ . '/fixtures/ReaderTest');
        $reader = new Reader();
        $reader->setConfig($config);
        $reader->read('a.yml');
        $this->assertEquals(false, $reader->getValue('behat'));
        $this->assertEquals(false, $reader->getValue('npm'));
        $this->assertEquals(true, $reader->getValue('pdo'));
        $this->assertEquals(true, $reader->getValue('phpcs'));
        $this->assertEquals(5.6, $reader->getValue('phpMin'));
        $this->assertEquals(7.4, $reader->getValue('phpMax'));
        $this->assertEquals(true, $reader->getValue('phpCoverage'));
        $this->assertEquals(true, $reader->getValue('postgres'));
        $this->assertEquals(4, $reader->getValue('recipeMajor'));
        $this->assertEquals(4.4, $reader->getValue('recipeMinorMin'));
        $this->assertEquals(4.6, $reader->getValue('recipeMinorMax'));
    }
    // TODO testB
}
