<?php

namespace Emteknetnz\TravisUtility\Tests;

use Emteknetnz\TravisUtility\Service\Config;
use Emteknetnz\TravisUtility\Service\Reader;
use PHPUnit\Framework\TestCase;

class ReaderTest extends TestCase
{

    public function testA(): void
    {
        $this->readerTest('a.yml', [
            'behat' => false,
            'npm' => false,
            'memoryLimit' => READER::DEFAULT_MEMORY_LIMIT,
            'pdo' => true,
            'phpcs' => true,
            'phpMin' => 5.6,
            'phpMax' => 7.4,
            'phpCoverage' => true,
            'postgres' => true,
            'recipeMajor' => 4,
            'recipeMinorMin' => 4.4,
            'recipeMinorMax' => 4.6
        ]);
    }

    public function testB(): void
    {
        $this->readerTest('b.yml', [
            'behat' => true,
            'npm' => false,
            'memoryLimit' => 3,
            'pdo' => false,
            'phpcs' => true,
            'phpMin' => 5.6,
            'phpMax' => 7.3,
            'phpCoverage' => false,
            'postgres' => true,
            'recipeMajor' => READER::DEFAULT_RECIPE_MAJOR,
            'recipeMinorMin' => 4.4,
            'recipeMinorMax' => 4.4
        ]);
    }

    public function testC(): void
    {
        $this->readerTest('c.yml', [
            'behat' => true,
            'npm' => true,
            'memoryLimit' => 2,
            'pdo' => false,
            'phpcs' => true,
            'phpMin' => 7.1,
            'phpMax' => 7.4,
            'phpCoverage' => false,
            'postgres' => true,
            'recipeMajor' => READER::DEFAULT_RECIPE_MAJOR,
            'recipeMinorMin' => READER::DEFAULT_RECIPE_MINOR_MIN,
            'recipeMinorMax' => READER::DEFAULT_RECIPE_MINOR_MAX
        ]);
    }

    private function readerTest(string $filename, array $expecteds): void
    {
        $config = new Config();
        $config->setValue('dir', __DIR__ . '/fixtures/ReaderTest');
        $reader = new Reader($config);
        $reader->read($filename);
        foreach ($expecteds as $key => $expected) {
            $actual = $reader->getValue($key);
            $this->assertEquals($expected, $actual, $key);
        }
    }
}
