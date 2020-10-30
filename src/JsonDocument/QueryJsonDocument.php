<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\JsonDocument;

class QueryJsonDocument
{
    /**
     * @var array
     */
    private $query;

    public function __construct()
    {
        $this->query = [];
    }

    public function withIncludeMetadata(): self
    {
        $clone = clone $this;
        $clone->query['includeMetadata'] = true;
        return $clone;
    }

    public function getAll(): array
    {
        return $this->query;
    }
}
