<?php

namespace Emteknetnz\TravisUtility\Service;

/**
 * Very simple key value based config
 */
class Config
{

    /*
     * https://github.com/silverstripeltd/github-issue-search-client/blob/master/src/repos.json
     * which is itself a super-set of
     * https://github.com/silverstripe/silverstripe-installer/blob/4/composer.json
     * https://github.com/silverstripe/recipe-cms/blob/4/composer.json
     * https://github.com/silverstripe/recipe-core/blob/4/composer.json
     *
     * We also need to include anything in recipe-testing, notably behat-extension, as this
     * is used to determine if we need ROOT_COMPOSER_VERSION
     * https://github.com/silverstripe/recipe-testing/blob/1/composer.json
     */
    private const CORE_MODULES_DIRECTORIES = [
        "silverstripe-admin",
        "silverstripe-asset-admin",
        "silverstripe-assets",
        "silverstripe-campaign-admin",
        "silverstripe-cms",
        "silverstripe-config",
        "silverstripe-errorpage",
        "silverstripe-framework",
        "silverstripe-graphql",
        "silverstripe-installer",
        "recipe-cms",
        "recipe-core",
        "recipe-plugin",
        "silverstripe-reports",
        "silverstripe-siteconfig",
        "silverstripe-versioned",
        "silverstripe-versioned-admin",
        "silverstripe-simple",
        // recently added:
        "silverstripe-login-forms",
        // recipe-testing
        "silverstripe-behat-extension",
        "silverstripe/serve"
    ];

    private $data = [];

    public function isCoreModule(string $subPath): bool
    {
        $subdir = preg_replace('@.+/(.+?)$@', '$1', $subPath);
        return in_array($subdir, self::CORE_MODULES_DIRECTORIES);
    }

    public function getValue(string $key)
    {
        return $this->data[$key] ?? null;
    }

    /**
     * Read a .config file
     */
    public function readConfigFile(): void
    {
        $configPath = __DIR__ . '/../../.config';
        if (!file_exists($configPath)) {
            echo "Could not find .config\n";
            die;
        }
        $lines = preg_split('/[\r\n]+/', file_get_contents($configPath));
        foreach ($lines as $line) {
            if (empty($line)) {
                continue;
            }
            if (substr($line, 0, 1) == '#') {
                continue;
            }
            $kv = preg_split("/=/", $line);
            $key = $kv[0];
            $value = $kv[1];
            $this->data[$key] = $value;
        }
    }

    public function setValue(string $key, string $value): void
    {
        $this->data[$key] = $value;
    }
}
