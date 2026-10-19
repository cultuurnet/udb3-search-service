<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Sort;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\BuilderInterface;
use CultuurNet\UDB3\Search\SortOrder;

final class NestedFieldSort implements BuilderInterface
{
    public function __construct(
        private readonly string $field,
        private readonly SortOrder $order,
        private readonly string $path,
        private readonly BuilderInterface $filter
    ) {
    }

    public function toArray(): array
    {
        return [
            $this->field => [
                'order' => $this->order->value,
                'nested' => [
                    'path' => $this->path,
                    'filter' => $this->filter->toArray(),
                ],
            ],
        ];
    }
}
