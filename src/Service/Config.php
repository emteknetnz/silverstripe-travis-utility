<?php

namespace Emteknetnz\TravisUtility\Service;

/**
 * Very simple key value based config
 */
class Config
{
    private $data = [];

    public function getValue(string $key)
    {
        return $this->data[$key] ?? null;
    }

    /**
     * Read a .config file
     */
    public function readConfigFile(): void
    {
        $configPath = '';
        $paths = ['./.config', '../.config', '../../.config'];
        foreach ($paths as $path) {
            if (file_exists($path)) {
                $configPath = $path;
                break;
            }
        }
        if (empty($configPath)) {
            echo "Could not find .config\n";
            die;
        }
        $lines = preg_split("/[\r\n]+/", file_get_contents($configPath));
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
