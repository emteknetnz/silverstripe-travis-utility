<?php

namespace Emteknetnz\TravisUtility\Service;

class Writer
{

    private $options = [];

    private $lines = [];

    public function __construct(array $options)
    {
        // TODO: pass in as param, is a combination of .config (min recipe) + reader (postgres)
        // may want to do it as $config, $readValues, and merge them in here, or somewhere else
        $options = [
            'phpMin' => 5.6,
            'recipeMinorMin' => 4.3,
            'recipeMinorMax' => 4.6,
            'recipeMajor' => 4,
            'postgres' => true,
            'behat' => false,
            'phpcs' => true,
            'phpCoverage' => true
        ];
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

    private function addLines($lines): void
    {
        $this->lines[] = array_merge($this->lines[], $lines);
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

    private function addMatrix(): void
    {
        // TODO =]
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
