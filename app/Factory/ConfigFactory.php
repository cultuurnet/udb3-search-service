<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Factory;

use Noodlehaus\Config;
use Noodlehaus\Parser\Php;

final class ConfigFactory
{
    public static function create(string $configDir): Config
    {
        $configFiles = [
            $configDir . '/config.php',
        ];
        return Config::load($configFiles, new Php());
    }
}
