<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Sort;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\BuilderInterface;
use CultuurNet\UDB3\Search\SortOrder;
use ONGR\ElasticsearchDSL\BuilderInterface as OngrBuilderInterface;

final class FieldSort implements BuilderInterface, OngrBuilderInterface
{
    public function __construct(
        private readonly string $field,
        private readonly SortOrder $order
    ) {
    }

    public function toArray(): array
    {
        return [
            $this->field => [
                'order' => $this->order->value,
            ],
        ];
    }

    // Lets ongr's containers accept this class until they are replaced; remove together with ongr.
    public function getType(): string
    {
        return 'sort';
    }
}
