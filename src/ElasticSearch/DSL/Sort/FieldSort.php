<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Sort;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\BuilderInterface;
use CultuurNet\UDB3\Search\SortOrder;

final class FieldSort implements BuilderInterface
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
}
