<?php

namespace Emteknetnz\TravisUtility\Service;

class Writer
{

    private $options = [];

    private $lines = [];

    public const $required

    public function __construct(array $options)
    {
        // TODO: pass in as param, is a combination of .config (min recipe) + reader (postgres)
        // may want to do it as $config, $readValues, and merge them in here, or somewhere else
        $requiredKeys = [
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
        foreach ($requiredKeys as $key) {
            if (!isset($options[$key])) {
                echo "Missing options key $key\n";
                die;
            }
        }
        $this->options = $options;
    }

    public function write(): void
    {
        $this->addIntro();
        $this->addServices();
        $this->addCache();
        $this->addEnv();
        $this->addMatrix();
        $this->addBeforeScript();
        $this->addScript();
        $this->addAfterSuccess();
    }

    public function getLines(): array
    {
        return $this->lines;
    }

    private function addLines($lines): void
    {
        $this->lines = array_merge($this->lines, $lines);
    }

    private function addIntro(): void
    {
        $lines = [
            'language: php',
            '',
            'dist: xenial',
            '',
        ];
        $this->addLines($lines);
    }

    private function addServices(): void
    {
        $lines = [
            'services:',
            '- mysql',
        ];
        if ($this->options['postgres']) {
            $lines[] = '- postgresql';
        }
        $lines[] = '';
        $this->addLines($lines);
    }

    private function addCache(): void
    {
        $lines = [
            'cache:',
            '  directories:',
            '    - $HOME/.composer/cache/files',
            '',
        ];
        $this->addLines($lines);
    }

    private function addEnv(): void
    {
        $composerRootVersion = $this->options['composerRootVersion'];
        $lines = [
            'env:',
            '  global:',
            "    - COMPOSER_ROOT_VERSION=\"$composerRootVersion.x-dev\"",
            '',
        ];
        $this->addLines($lines);
    }

    /*
matrix:
  include:
    - php: 5.6
      env: DB=MYSQL RECIPE_VERSION=4.4.x-dev PHPUNIT_TEST=1 PHPCS_TEST=1
    - php: 7.1
      env: DB=MYSQL RECIPE_VERSION=4.5.x-dev PHPUNIT_COVERAGE_TEST=1 PDO=1
    - php: 7.2
      env: DB=PGSQL RECIPE_VERSION=4.6.x-dev PHPUNIT_TEST=1
    - php: 7.3
      env: DB=MYSQL RECIPE_VERSION=4.6.x-dev PHPUNIT_TEST=1
    - php: 7.4
      env: DB=MYSQL RECIPE_VERSION=4.x-dev PHPUNIT_TEST=1
     */

    private function addMatrix(): void
    {
        $minMatrixLength = 5;
        $pdoPhp = 7.1;
        $postgresPhp = 7.2;
        $phpcsI = 0;
        $phpCoverageI = 1;
        $behatI = 2;
        $npmI = 3;

        if ($pdoPhp == $postgresPhp) {
            echo '$pdoPhp and $postgresPhp should be different' . "\n";
            die;
        }

        // TODO: move this stuff to private methods

        // TODO: unit test all this

        // php
        $phps = [5.6, 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7, 7.8, 7.9];
        $phpMin = $this->options['phpMin'];
        $phpMax = $this->options['phpMax'];
        $phpMinI = array_search($phpMin, $phps);
        if ($phpMinI === false || $phpMinI === null) {
            echo "Invalid phpMin";
            die;
        }
        $phpMaxI = array_search($phpMax, $phps);
        if ($phpMinI === false || $phpMinI === null) {
            echo "Invalid phpMax";
            die;
        }
        $myPhps = [];
        for ($i = $phpMinI; $i <= $phpMaxI; $i++) {
            $myPhps[] = $phps[$i];
        }
        while (count($myPhps) < $minMatrixLength) {
            $myPhps[] = $phpMax;
        }

        // recipe
        $recipeMinors = [4.0, 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 4.8, 4.9];
        $recipeMinorMin = $this->options['recipeMinorMin'];
        $recipeMinorMax = $this->options['recipeMinorMax'];
        $recipeMajor = $this->options['recipeMajor'];

        $recipeMinorMinI = array_search($recipeMinorMin, $recipeMinors);
        if ($recipeMinorMinI === false || $recipeMinorMinI === null) {
            echo "Invalid recipeMinorMin";
            die;
        }
        $recipeMinorMaxI = array_search($recipeMinorMax, $recipeMinors);
        if ($recipeMinorMaxI === false || $recipeMinorMaxI === null) {
            echo "Invalid recipeMinorMax";
            die;
        }
        $myRecipes = [];

        if ($this->options['coreModule']) {
            while (count($myRecipes) < $minMatrixLength) {
                $myRecipes[] = $recipeMinorMax;
            }
        } else {
            for ($i = $recipeMinorMinI; $i <= $recipeMinorMaxI; $i++) {
                $myRecipes[] = $recipeMinors[$i];
            }
            while (count($myRecipes) < ($minMatrixLength - 1)) {
                $myRecipes[] = $recipeMinorMax;
            }
            $myRecipes[] = $recipeMajor;
        }

        // lines
        $lines = [
            'matrix:',
            '  include:',
        ];
        $lastPhp = '';
        $lastEnv = '';
        for ($i = 0; $i < count($myRecipes); $i++) {
            // TODO: confirm we can replace any silverstripe/installer with silverstripe/recipe-cms
            $recipe = (string) $myRecipes[$i];
            $php = (string) isset($myPhps[$i]) ? $myPhps[$i] : $phpMax;
            $data = [];
            $data[] = $this->options['postgres'] && $php == $postgresPhp ? 'DB=PGSQL' : 'DB=MYSQL';
            $data[] = "RECIPE_VERSION=$recipe.x-dev";
            $data[] = $this->options['phpCoverage'] && $i == $phpCoverageI ? 'PHPUNIT_COVERAGE_TEST=1' : 'PHPUNIT_TEST=1';
            if ($this->options['phpcs'] && $i == $phpcsI) {
                $data[] = 'PHPCS_TEST=1';
            }
            if ($this->options['pdo'] && $php == $pdoPhp) {
                $data[] = 'PDO=1';
            }
            if ($this->options['npm'] && $i == $npmI) {
                $data[] = 'NPM_TEST=1';
            }
            if ($this->options['behat'] && $i == $behatI) {
                $data[] = 'BEHAT_TEST=1';
            }
            $env = implode(' ', $data);
            if ($php == $lastPhp && strpos($lastEnv, $env) === 0) {
                break;
            }
            $lines[] = "    - php: $php";
            $lines[] = "      env: $env";
            $lastPhp = $php;
            $lastEnv = $env;
        }
        $lines[] = '';
        $this->addLines($lines);
    }

    private function addBeforeScript(): void
    {
        // composer require/install/update cli options:
        // https://getcomposer.org/doc/03-cli.md
        $lines = [
            'before_script:',
            '  - phpenv rehash',
            "  - phpenv config-rm xdebug.ini",
            '',
            '  - composer validate',
            // TODO: other requirements are needed sometimes
            // TODO: consider including everything together on a single line
            '  - composer require --no-update silverstripe/recipe-cms:$RECIPE_VERSION',
            '  # Fix for running phpunit 5 on php 7.4+',
            '  - composer require --no-update sminnee/phpunit-mock-objects:^3',
        ];
        if ($this->options['postgres']) {
            $lines[] = '- if [[ $DB == PGSQL ]]; then composer require --no-update silverstripe/postgresql:^2; fi';
        }
        $lines[] = '  - composer install --prefer-dist --no-interaction --no-progress --no-suggest --optimize-autoloader --verbose --profile';
        $lines[] = '';
        $this->addLines($lines);
    }

    private function addScript(): void
    {
        // TODO: BEHAT_TEST
        // TODO: NPM_TEST
        $lines = [
            '  - if [[ $PHPUNIT_TEST ]]; then vendor/bin/phpunit tests/; fi'
        ];
        if ($this->options['phpCoverage']) {
            $lines[] = '  - if [[ $PHPUNIT_COVERAGE_TEST ]]; then phpdbg -qrr vendor/bin/phpunit --coverage-clover=coverage.xml; fi';
        }
        if ($this->options['phpcs']) {
            $lines[] = '  - if [[ $PHPCS_TEST ]]; then vendor/bin/phpcs src/ tests/ ; fi';
        }
        $this->addLines($lines);
    }

    private function addAfterSuccess(): void
    {
        $lines = [
            'after_success:'
        ];
        if ($this->options['phpCoverage']) {
            $lines[] = '  - if [[ $PHPUNIT_COVERAGE_TEST ]]; then bash <(curl -s https://codecov.io/bash) -f coverage.xml; fi';
        }
        if (count($lines) > 1) {
            $this->addLines($lines);
        }
    }
}
