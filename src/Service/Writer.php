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
        // TODO
    }

    private function addScript(): void
    {
        // TODO
    }

    private function addAfterSuccess(): void
    {
        // TODO
    }
}
