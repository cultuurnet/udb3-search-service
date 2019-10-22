<?php declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Factory;

use Noodlehaus\Config;
use Noodlehaus\Parser\Yaml;

class ConfigFactory
{
    public static function create(string $configDir): Config
    {
        $files = [];
        foreach (scandir($configDir) as $configFile) {
            if (preg_match('/.*\.yml/', $configFile) && !preg_match('/.*\.dist.*/', $configFile)) {
                $files[] = $configDir . '/' . $configFile;
            }
        }

        return Config::load($files, new Yaml());
    }
}
