<?php

declare(strict_types=1);

use ComposerUnused\ComposerUnused\Configuration\Configuration;
use ComposerUnused\ComposerUnused\Configuration\NamedFilter;
use Webmozart\Glob\Glob;

return static function (Configuration $config): Configuration {
    $config
        ->setAdditionalFilesFor('icanhazstring/composer-unused', [
            __FILE__,
            ...Glob::glob(__DIR__ . '/config/*.php'),
        ]);

    // Actually is a dependency of guzzlehttp/guzzle
    // We could throw Guzzle out and import the Symfony HTTP clients, but that would require actual domain code changes
    $config->addNamedFilter(NamedFilter::fromString('php-http/guzzle7-adapter'));

    return $config;
};