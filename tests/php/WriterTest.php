<?php

namespace Emteknetnz\TravisUtility\Tests;

use ReflectionMethod;
use Emteknetnz\TravisUtility\Service\Writer;
use PHPUnit\Framework\TestCase;

class WriterTest extends TestCase
{
    public function testMatrixOne(): void
    {
        $options = [
            'behat' => false,
            'coreModule' => false,
            'npm' => false,
            'pdo' => true,
            'phpcs' => true,
            'phpCoverage' => true,
            'phpMin' => 5.6,
            'phpMax' => 7.4,
            'postgres' => true,
            'recipeMajor' => 4,
            'recipeMinorMin' => 4.4,
            'recipeMinorMax' => 4.6,
        ];
        $expected = [
            'matrix:',
            '  include:',
            '    - php: 5.6',
            '      env: DB=MYSQL RECIPE_VERSION=4.4.x-dev PHPUNIT_TEST=1 PHPCS_TEST=1',
            '    - php: 7.1',
            '      env: DB=MYSQL RECIPE_VERSION=4.5.x-dev PHPUNIT_COVERAGE_TEST=1 PDO=1',
            '    - php: 7.2',
            '      env: DB=PGSQL RECIPE_VERSION=4.6.x-dev PHPUNIT_TEST=1',
            '    - php: 7.3',
            '      env: DB=MYSQL RECIPE_VERSION=4.6.x-dev PHPUNIT_TEST=1',
            '    - php: 7.4',
            '      env: DB=MYSQL RECIPE_VERSION=4.x-dev PHPUNIT_TEST=1',
            ''
        ];
        $this->lineTest($options, $expected);
    }

    public function testMatrixTwo(): void
    {
        $options = [
            'behat' => true,
            'coreModule' => true,
            'npm' => true,
            'pdo' => true,
            'phpcs' => true,
            'phpCoverage' => true,
            'phpMin' => 7.1,
            'phpMax' => 7.4,
            'postgres' => true,
            'recipeMinorMin' => 4.4,
            'recipeMinorMax' => 4.6,
            'recipeMajor' => 4,
        ];
        $expected = [
            'matrix:',
            '  include:',
            '    - php: 7.1',
            '      env: DB=MYSQL RECIPE_VERSION=4.6.x-dev PHPUNIT_TEST=1 PHPCS_TEST=1 PDO=1',
            '    - php: 7.2',
            '      env: DB=PGSQL RECIPE_VERSION=4.6.x-dev PHPUNIT_COVERAGE_TEST=1',
            '    - php: 7.3',
            '      env: DB=MYSQL RECIPE_VERSION=4.6.x-dev PHPUNIT_TEST=1 BEHAT_TEST=1',
            '    - php: 7.4',
            '      env: DB=MYSQL RECIPE_VERSION=4.6.x-dev PHPUNIT_TEST=1 NPM_TEST=1',
            ''
        ];
        $this->lineTest($options, $expected);
    }

    private function lineTest(array $options, array $expected)
    {
        $method = new ReflectionMethod(Writer::class, 'addMatrix');
        $method->setAccessible(true);
        $writer = new Writer($options);
        $method->invoke($writer);
        $lines = $writer->getLines();
        for ($i =0; $i < count($lines); $i++) {
            $this->assertEquals($expected[$i], $lines[$i]);
        }
    }
}
