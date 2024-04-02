<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/web',
    ])
    ->withImportNames()
    ->withPhpSets(php74: true)
    ->withSets([SetList::TYPE_DECLARATION]) //rule to force typed declarations
    ->withRules([
        AddVoidReturnTypeWhereNoReturnRector::class,
    ]);