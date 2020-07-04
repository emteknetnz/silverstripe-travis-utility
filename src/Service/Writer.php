<?php

namespace Emteknetnz\TravisUtility\Service;

class Writer
{

    public const OPTION_KEYS = [
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

    // TODO: list of @asset-admin like behat tests based on subdir e.g. silverstripe-asset-admin
    // possibly should live in a different class

    private $options = [];

    private $lines = [];

    public function __construct(array $options)
    {
        foreach (self::OPTION_KEYS as $key) {
            if (!isset($options[$key])) {
                echo "Missing options key $key\n";
                die;
            }
        }
        $this->options = $options;
    }

    public function getLines(): array
    {
        return $this->lines;
    }

    public function write(): void
    {
        $this->addIntro();
        $this->addServices();
        $this->addCache();
        $this->addAddons();
        $this->addEnv();
        $this->addMatrix();
        $this->addBeforeScript();
        $this->addScript();
        $this->addAfterSuccess();
        $this->addAfterFailure();
        // TODO: update this
        file_put_contents('output.txt', implode("\n", $this->lines));
    }

    private function addAddons(): void
    {
        if (!$this->options['behat']) {
            return;
        }
        // the tidy package is used to make a slightly nicer diff in history view
        $lines = [
            'addons:',
            '  apt:',
            '    packages:',
            '      - tidy',
            '      - chromium-chromedriver',
            '      - chromium-browser',
            ''
        ];
        $this->addLines($lines);
    }

    private function addAfterFailure(): void
    {
        if (!$this->options['behat']) {
            return;
        }
        $lines = [
            'after_failure:',
            '  - if [[ $BEHAT_TEST ]]; then php ./vendor/silverstripe/framework/tests/behat/travis-upload-artifacts.php --if-env BEHAT_TEST,ARTIFACTS_BUCKET,ARTIFACTS_KEY,ARTIFACTS_SECRET --target-path $TRAVIS_REPO_SLUG/$TRAVIS_BUILD_ID/$TRAVIS_JOB_ID --artifacts-base-url https://s3.amazonaws.com/$ARTIFACTS_BUCKET/ --artifacts-path ./artifacts/; fi',
            ''
        ];
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
        $lines[] = '';
        if (count($lines) > 2) {
            $this->addLines($lines);
        }
    }

    private function addBeforeScript(): void
    {
        // composer require/install/update cli options:
        // https://getcomposer.org/doc/03-cli.md
        $lines = [];
        $lines[] = 'before_script:';
        if ($this->options['behat']) {
            $lines[] = '  # Extra $PATH';
            $lines[] = '  - export PATH=/usr/lib/chromium-browser/:$PATH';
            $lines[] = '';
        }
        $lines[] = '  # Init PHP';
        $lines[] = '  - phpenv rehash';
        $lines[] = '  - phpenv config-rm xdebug.ini';
        // 4GB ram because a few builds using things like PHPCS seem to require this
        // Am assuming there's no downside to having all builds to allow PHP to use this much RAM
        // TODO: confirm ram usage
        $lines[] = '  - echo \'memory_limit = 4G\' >> ~/.phpenv/versions/$(phpenv version-name)/etc/conf.d/travis.ini';
        $lines[] = '  - echo \'always_populate_raw_post_data = -1\' >> ~/.phpenv/versions/$(phpenv version-name)/etc/conf.d/travis.ini';
        $lines[] = '';
        $lines[] = '  # Install composer requirements';
        $lines[] = '  # sminnee/phpunit-mock-objects is a fix for running phpunit 5 on php 7.4+';
        $lines[] = '  - composer validate';
        // TODO: other composer requirements are needed sometimes - will using recipe always be enough?
        // TODO: refactor consider including everything together on a single line
        $requirements = [
            'silverstripe/recipe-cms:$RECIPE_VERSION',
            'sminnee/phpunit-mock-objects:^3'
        ];
        if ($this->options['behat']) {
            $requirements[] = 'silverstripe/recipe-testing:^1';
        }
        $lines[] = '  - composer require --no-update ' . implode(' ', $requirements);
        if ($this->options['postgres']) {
            $lines[] = '  - if [[ $DB == PGSQL ]]; then composer require --no-update silverstripe/postgresql:^2; fi';
        }
        $lines[] = '  - composer install --prefer-dist --no-interaction --no-progress --no-suggest --verbose --profile';
        $lines[] = '';

        if ($this->options['behat']) {
            $lines[] = '  # Remove preinstalled Chrome (google-chrome)';
            $lines[] = '  # this would conflict with our chromium-browser installation';
            $lines[] = '  # and its version is incompatible with chromium-chromedriver';
            $lines[] = '  - sudo apt-get remove -y --purge google-chrome-stable || true';
            $lines[] = '';
            $lines[] = '  # Start behat services';
            $lines[] = '  - if [[ $BEHAT_TEST ]]; then mkdir artifacts; fi';
            $lines[] = '  - if [[ $BEHAT_TEST ]]; then cp composer.lock artifacts/; fi';
            $lines[] = '  - if [[ $BEHAT_TEST ]]; then sh -e /etc/init.d/xvfb start; sleep 3; fi';
            $lines[] = '  - if [[ $BEHAT_TEST ]]; then (chromedriver > artifacts/chromedriver.log 2>&1 &); fi';
            $lines[] = '  - if [[ $BEHAT_TEST ]]; then (vendor/bin/serve --bootstrap-file vendor/silverstripe/cms/tests/behat/serve-bootstrap.php &> artifacts/serve.log &); fi';
            $lines[] = '';
        }

        if ($this->options['npm']) {
            $lines[] = '  # Install NPM dependencies';
            $lines[] = '  - if [[ $NPM_TEST ]]; then rm -rf client/dist && nvm install && nvm use && npm install -g yarn && yarn install --network-concurrency 1 && (cd vendor/silverstripe/admin && yarn install --network-concurrency 1) && yarn run build; fi';
            $Lines[] = '';
        }
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
        ];
        if ($this->options['behat']) {
            // DISPLAY and XVFBARGS are probably not required
            // $lines[] = '    - DISPLAY=":99"';
            // $lines[] = '    - XVFBARGS=":99 -ac -screen 0 1024x768x16"';
            $lines[] = '    - SS_BASE_URL="http://localhost:8080/"';
            $lines[] = '    - SS_ENVIRONMENT_TYPE="dev"';
        }
        $lines[] = '';
        $this->addLines($lines);
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

    private function addLines($lines): void
    {
        $this->lines = array_merge($this->lines, $lines);
    }

    private function addMatrix(): void
    {
        // TODO: refactor move these to private const for better visibility?
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

        // TODO: refactor move this stuff to private methods

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
            $recipe = (string)$myRecipes[$i];
            $php = (string)isset($myPhps[$i]) ? $myPhps[$i] : $phpMax;
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
                $data[] = 'BEHAT_TEST=@module # <-- replace this with module name e.g. @asset-admin';
            }
            $env = implode(' ', $data);
            // don't add any more entires to matrix, even if below minimum matrix size, if all
            // we're doing is adding another entry with the same php version that only does a phpunit test
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

    private function addScript(): void
    {
        $lines = [];
        $lines[] = '  # Run tests';
        if ($this->options['phpcs']) {
            $lines[] = '  - if [[ $PHPCS_TEST ]]; then vendor/bin/phpcs src tests *.php --ignore=host-map.php; fi';
        }
        if ($this->options['npm']) {
            $lines[] = '  - if [[ $NPM_TEST ]]; then git diff-files --quiet -w --relative=client; fi';
            $lines[] = '  - if [[ $NPM_TEST ]]; then git diff --name-status --relative=client; fi';
            $lines[] = '  - if [[ $NPM_TEST ]]; then yarn run test; fi';
            $lines[] = '  - if [[ $NPM_TEST ]]; then yarn run lint; fi';
            $lines[] = '';
        }
        if ($this->options['phpCoverage']) {
            $lines[] = '  - if [[ $PHPUNIT_COVERAGE_TEST ]]; then phpdbg -qrr vendor/bin/phpunit --coverage-clover=coverage.xml; fi';
        } else {
            $lines[] = '  - if [[ $PHPUNIT_TEST ]]; then vendor/bin/phpunit tests/; fi';
        }
        if ($this->options['behat']) {
            $lines[] = '  - if [[ $BEHAT_TEST ]]; then vendor/bin/behat $BEHAT_TEST; fi';
        }
        $lines[] = '';
        $this->addLines($lines);
    }

    private function addServices(): void
    {
        $lines = [
            'services:',
            '  - mysql',
        ];
        if ($this->options['postgres']) {
            $lines[] = '  - postgresql';
        }
        if ($this->options['behat']) {
            $lines[] = '  - xvfb';
        }
        $lines[] = '';
        $this->addLines($lines);
    }
}
