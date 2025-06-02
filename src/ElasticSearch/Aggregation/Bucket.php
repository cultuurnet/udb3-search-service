<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Aggregation;

final class Bucket
{
    private string $key;

    private int $count;

    public function __construct(string $key, int $count)
    {
        $this->key = $key;
        $this->count = $count;
    }


    public function getKey(): string
    {
        return $this->key;
    }


    public function getCount(): int
    {
        return $this->count;
    }
}
