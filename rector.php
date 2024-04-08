<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromAssignsRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictSetUpRector;

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
    ])
    ->withSkip([
        /*
         * See https://jira.publiq.be/browse/III-6139
        Remove these skips once we upgrade to PHP 8, and we have a union type
        Currently it will change code:
            @var RegionServiceInterface|MockObject
        to:
            private MockObject;

        breaking the code.
        */
        TypedPropertyFromStrictSetUpRector::class,
        TypedPropertyFromAssignsRector::class,
        // End skipping until PHP 8

        // We don't always want short array functions because they are more difficult to read (longer line) than old school closure syntax
        ClosureToArrowFunctionRector::class
    ]);