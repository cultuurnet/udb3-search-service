<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Factory;

use Noodlehaus\Config;
use Noodlehaus\Parser\Yaml;

class ConfigFactory
{
    public static function create(string $configDir): Config
    {
        $configFiles = [
            $configDir . '/config.yml',
            $configDir . '/facet_mapping_facilities.yml',
            $configDir . '/facet_mapping_regions.yml',
            $configDir . '/facet_mapping_themes.yml',
            $configDir . '/facet_mapping_types.yml',
            $configDir . '/features.yml',
        ];
        return Config::load($configFiles, new Yaml());
    }
}
