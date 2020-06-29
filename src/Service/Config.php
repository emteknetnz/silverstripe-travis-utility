<?php

namespace Emteknetnz\TravisUtility\Service;

/**
 * Very simple key value based config
 */
class Config
{
    private $data = [];

    public function getValue(string $key): string
    {
        return $this->data[$key];
    }

    public function setValue(string $key, string $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * Read a .config file
     */
    public function readConfigFile(): void
    {
        $lines = preg_split("/[\r\n]+/", file_get_contents('../../.config'));
        foreach ($lines as $line) {
            if (empty($line)) {
                continue;
            }
            $kv = preg_split("/=/", $line);
            $key = $kv[0];
            $value = $kv[1];
            $this->data[$key] = $value;
        }
    }
}
