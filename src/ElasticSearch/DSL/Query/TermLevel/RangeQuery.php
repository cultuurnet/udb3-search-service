<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\TermLevel;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\BuilderInterface;
use ONGR\ElasticsearchDSL\BuilderInterface as OngrBuilderInterface;

final class RangeQuery implements BuilderInterface, OngrBuilderInterface
{
    public const GT = 'gt';
    public const GTE = 'gte';
    public const LT = 'lt';
    public const LTE = 'lte';

    /**
     * @param array<self::GT|self::GTE|self::LT|self::LTE, string|int|float> $parameters
     */
    public function __construct(
        private readonly string $field,
        private readonly array $parameters
    ) {
    }

    public function toArray(): array
    {
        $parameters = $this->parameters;

        if (isset($parameters[self::GTE], $parameters[self::GT])) {
            unset($parameters[self::GT]);
        }

        if (isset($parameters[self::LTE], $parameters[self::LT])) {
            unset($parameters[self::LT]);
        }

        return ['range' => [$this->field => $parameters]];
    }

    // Lets ongr's containers accept this class until they are replaced; remove together with ongr.
    public function getType(): string
    {
        return 'range';
    }
}
