<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\TermLevel;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\BuilderInterface;

final class RangeQuery implements BuilderInterface
{
    public const GT = 'gt';
    public const GTE = 'gte';
    public const LT = 'lt';
    public const LTE = 'lte';

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
}
