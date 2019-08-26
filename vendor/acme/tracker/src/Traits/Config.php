<?php

namespace Acme\Tracker\Traits;

/**
 * Trait Config
 * @package Acme\Tracker\Traits
 */
trait Config
{
    /**
     * @return array
     */
    protected function parseConfig(): array
    {
        $iniFile = __DIR__ .
            DIRECTORY_SEPARATOR .
            '..' .
            DIRECTORY_SEPARATOR .
            '..' .
            DIRECTORY_SEPARATOR .
            'config' .
            DIRECTORY_SEPARATOR .
            'config.ini.php';
        $parsedData = parse_ini_file($iniFile, true);

        if ((bool)$parsedData === false) {
            return [];
        }

        return $parsedData;
    }

    /**
     * @param string $item
     * @return string
     */
    public function getConfig($item): string
    {
        if (!empty($_SESSION['config']) && !empty($_SESSION['config'][$item])) {
            return $_SESSION['config'][$item];
        }

        $config = $this->parseConfig();

        if (empty($item) || !is_array($config) || count($config) === 0 || !array_key_exists($item, $config)) {
            return '';
        }

        $_SESSION['config'][$item] = $config[$item];

        return trim($config[$item]);
    }
}
