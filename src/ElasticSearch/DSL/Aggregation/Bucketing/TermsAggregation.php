<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Aggregation\Bucketing;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\NamedBuilderInterface;

final class TermsAggregation implements NamedBuilderInterface
{
    private array $extraParameters = [];

    public function __construct(
        private readonly string $name,
        private readonly string $field
    ) {
    }

    public function addParameter(string $key, mixed $value): void
    {
        $this->extraParameters[$key] = $value;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function toArray(): array
    {
        return [
            'terms' => array_merge(
                ['field' => $this->field],
                $this->extraParameters
            ),
        ];
    }
}
