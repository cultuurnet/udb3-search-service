<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\TermLevel;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\BuilderInterface;
use ONGR\ElasticsearchDSL\BuilderInterface as OngrBuilderInterface;

final class TermQuery implements BuilderInterface, OngrBuilderInterface
{
    public function __construct(
        private readonly string $field,
        private readonly string|bool|int|float $value
    ) {
    }

    public function toArray(): array
    {
        return ['term' => [$this->field => $this->value]];
    }

    // Lets ongr's containers accept this class until they are replaced; remove together with ongr.
    public function getType(): string
    {
        return 'term';
    }
}
