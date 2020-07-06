<?php

namespace Emteknetnz\TravisUtility\Service;

class Reader
{

    public const DEFAULT_COMPOSER_ROOT_VERSION = 99.9;

    public const DEFAULT_MEMORY_LIMIT = 2;

    public const DEFAULT_PHP_MAX = 0;

    public const DEFAULT_PHP_MIN = 99.9;

    public const DEFAULT_RECIPE_MAJOR = 0;

    public const DEFAULT_RECIPE_MINOR_MAX = 0;

    public const DEFAULT_RECIPE_MINOR_MIN = 99.9;

    public const DEFAULT_SRC_DIR = 'src';

    /**
     * @var Config
     */
    private $config = null;

    private $data = [];

    public function __construct(Config $config = null)
    {
        $this->config = $config;
        $this->setDefaultDataValues();
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getValue(string $key)
    {
        return $this->data[$key] ?? null;
    }

    public function read(string $subPath): void
    {
        $this->data['coreModule'] = $this->config->isCoreModule($subPath);
        if (!file_exists('src') && file_exists('code')) {
            $this->data['srcDir'] = 'code';
        }
        $dir = rtrim($this->config->getValue('dir'), '/');
        $path = preg_replace('@/\.travis\.yml$@', '', "$dir/$subPath");
        $ymlPath = preg_match('@\.yml$@', $path) ? $path : "$path/.travis.yml";
        $contents = file_get_contents($ymlPath);
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
                $this->parseBehat($line);
                $this->parseNpm($line);
                $this->parsePdo($line);
                $this->parsePhpVersions($line);
                $this->parsePhpcs($line);
                $this->parsePhpCoverage($line);
                $this->parsePostgres($line);
                $this->parseRecipeVersions($line);
                $this->parseSubsites($line);
            } else {
                $this->parseFrameworkTest($line);
                $this->parseMemoryLimit($line);
                $this->parseYarnCoverage($line);
                $this->parseYarnLint($line);
                $this->parseYarnTest($line);
            }
        }
    }

    private function parseBehat(string $line): void
    {
        if (!preg_match('/BEHAT_TEST/', $line)) {
            return;
        }
        $this->data['behat'] = true;
    }

    private function parseFrameworkTest(string $line): void
    {
        if (!preg_match('@silverstripe/frameworktest@', $line)) {
            return;
        }
        $this->data['frameworkTest'] = true;
    }

    private function parseMemoryLimit(string $line): void
    {
        if (!preg_match('/memory_limit *= *([0-9]+)([MG])/', $line, $m)) {
            return;
        }
        $memoryLimit = $m[1];
        if ($m[2] == 'M') {
            $memoryLimit = ceil($memoryLimit / 1024);
        }
        $this->data['memoryLimit'] = $memoryLimit;
    }

    private function parseNpm(string $line): void
    {
        if (!preg_match('/NPM_TEST=1/', $line)) {
            return;
        }
        $this->data['npm'] = true;
    }

    private function parsePdo(string $line): void
    {
        if (!preg_match('/PDO=1/', $line)) {
            return;
        }
        $this->data['pdo'] = true;
    }

    private function parsePhpCoverage(string $line): void
    {
        if (!preg_match('/PHPUNIT_COVERAGE_TEST=1/', $line)) {
            return;
        }
        $this->data['phpCoverage'] = true;
    }

    private function parsePhpcs(string $line): void
    {
        if (!preg_match('/PHPCS_TEST=1/', $line)) {
            return;
        }
        $this->data['phpcs'] = true;
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

    private function parsePostgres(string $line): void
    {
        if (!preg_match('/DB=PGSQL/', $line)) {
            return;
        }
        $this->data['postgres'] = true;
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

    private function parseSubsites(string $line): void
    {
        if (!preg_match('/SUBSITES=1/', $line)) {
            return;
        }
        $this->data['subsites'] = true;
    }

    private function parseYarnCoverage(string $line): void
    {
        if (!preg_match('/yarn run coverage/', $line)) {
            return;
        }
        $this->data['yarnCoverage'] = true;
    }

    private function parseYarnLint(string $line): void
    {
        if (!preg_match('/yarn run lint/', $line)) {
            return;
        }
        $this->data['yarnLint'] = true;
    }

    private function parseYarnTest(string $line): void
    {
        if (!preg_match('/yarn run test/', $line)) {
            return;
        }
        $this->data['yarnTest'] = true;
    }

    private function setDefaultDataValues()
    {
        $this->data = [
            'behat' => false,
            'composerRootVersion' => self::DEFAULT_COMPOSER_ROOT_VERSION,
            'coreModule' => false,
            'frameworkTest' => false,
            'memoryLimit' => self::DEFAULT_MEMORY_LIMIT,
            'npm' => false,
            'pdo' => false,
            'phpcs' => false,
            'phpCoverage' => false,
            'phpMin' => self::DEFAULT_PHP_MIN,
            'phpMax' => self::DEFAULT_PHP_MAX,
            'postgres' => false,
            'recipeMinorMin' => self::DEFAULT_RECIPE_MINOR_MIN,
            'recipeMinorMax' => self::DEFAULT_RECIPE_MINOR_MAX,
            'recipeMajor' => self::DEFAULT_RECIPE_MAJOR,
            'srcDir' => self::DEFAULT_SRC_DIR,
            'subsites' => false,
            'yarnCoverage' => false,
            'yarnLint' => false,
            'yarnTest' => false,
        ];
    }
}
