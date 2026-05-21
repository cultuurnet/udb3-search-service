<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Sort;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\BuilderInterface;

final class FieldSort implements BuilderInterface
{
    public const ASC = 'asc';
    public const DESC = 'desc';

    private ?BuilderInterface $nestedFilter = null;

    private array $parameters;

    public function __construct(
        private readonly string $field,
        private readonly string $order,
        array $parameters = []
    ) {
        $this->parameters = $parameters;
    }

    public function setNestedFilter(BuilderInterface $filter): void
    {
        $this->nestedFilter = $filter;
    }

    public function toArray(): array
    {
        if ($this->nestedFilter !== null && isset($this->parameters['nested_path'])) {
            $nestedPath = $this->parameters['nested_path'];
            $remaining = $this->parameters;
            unset($remaining['nested_path']);

            $fieldValue = array_merge(
                ['order' => $this->order],
                $remaining,
                [
                    'nested' => [
                        'path' => $nestedPath,
                        'filter' => $this->nestedFilter->toArray(),
                    ],
                ]
            );

            return [$this->field => $fieldValue];
        }

        return [$this->field => array_merge(['order' => $this->order], $this->parameters)];
    }
}
