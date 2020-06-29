<?php

namespace Emteknetnz\TravisUtility\Service;

class Reader
{

    public const DEFAULT_PHP_MIN = 99.9;
    public const DEFAULT_PHP_MAX = 0;
    public const DEFAULT_RECIPE_MINOR_MIN = 99.9;
    public const DEFAULT_RECIPE_MINOR_MAX = 0;
    public const DEFAULT_COMPOSER_ROOT_VERSION = 99.9; // TODO: need to read from .git somehow

    /**
     * @var Config
     */
    private $config = null;

    private $data = [];

    public function __construct()
    {
        $this->setDefaultDataValues();
    }

    public function setConfig(Config $config): void
    {
        $this->config = $config;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getValue(string $key)
    {
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }

    public function read(string $filename): void
    {
        $dir = $this->config->getValue('dir');
        $contents = file_get_contents("$dir/$filename");
        $lines = preg_split("/[\r\n]+/", $contents);
        $inMatrix = false;
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line == 'matrix:') {
                $inMatrix = true;
            } elseif (in_array($line, ['before_script:', 'script:'])) {
                $inMatrix = false;
            }
            if ($inMatrix) {
                $this->parsePhpVersions($line);
                $this->parseRecipeVersions($line);
                $this->parsePostgres($line);
                $this->parsePhpcs($line);
                $this->parsePhpCoverage($line);
                $this->parsePdo($line);
            } else {
                // not in matrix (nothing?)
            }
        }
    }

    private function setDefaultDataValues()
    {
        $this->data['phpMin'] = self::DEFAULT_PHP_MIN;
        $this->data['phpMax'] = self::DEFAULT_PHP_MAX;
        $this->data['recipeMinorMin'] = self::DEFAULT_RECIPE_MINOR_MIN;
        $this->data['recipeMinorMax'] = self::DEFAULT_RECIPE_MINOR_MAX;
        $this->data['composerRootVersion'] = self::DEFAULT_COMPOSER_ROOT_VERSION;
        $this->data['postgres'] = false;
        $this->data['phpcs'] = false;
        $this->data['phpCoverage'] = false;
        $this->data['pdo'] = false;
    }

    private function parsePhpVersions(string $line): void
    {
        if (!preg_match('/php: ([0-9]\.[0-9])/', $line, $m)) {
            return;
        }
        $version = $m[1];
        if ($version < $this->data['phpMin']) {
            $this->data['phpMin'] = $version;
        } elseif ($version > $this->data['phpMax']) {
            $this->data['phpMax'] = $version;
        }
    }

    private function parseRecipeVersions(string $line): void
    {
        if (!preg_match('/(RECIPE|INSTALLER)_VERSION=([0-9\.]+)\.x\-dev/', $line, $m)) {
            return;
        }
        $version = $m[2];
        if (preg_match('/\./', $version)) {
            if ($version < $this->data['recipeMinorMin']) {
                $this->data['recipeMinorMin'] = $version;
            } elseif ($version > $this->data['recipeMinorMax']) {
                $this->data['recipeMinorMax'] = $version;
            }
        } else {
            $this->data['recipeMajor'] = $version;
        }
    }

    private function parsePostgres(string $line): void
    {
        if (!preg_match('/DB=PGSQL/', $line)) {
            return;
        }
        $this->data['postgres'] = true;
    }

    private function parsePdo(string $line): void
    {
        if (!preg_match('/PDO=1/', $line)) {
            return;
        }
        $this->data['pdo'] = true;
    }

    private function parsePhpcs(string $line): void
    {
        if (!preg_match('/PHPCS_TEST=1/', $line)) {
            return;
        }
        $this->data['phpcs'] = true;
    }

    private function parsePhpCoverage(string $line): void
    {
        if (!preg_match('/PHPUNIT_COVERAGE_TEST=1/', $line)) {
            return;
        }
        $this->data['phpCoverage'] = true;
    }
}
