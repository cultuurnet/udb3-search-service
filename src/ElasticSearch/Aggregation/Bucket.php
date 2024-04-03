<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Aggregation;

use InvalidArgumentException;

final class Bucket
{
    private string $key;

    private int $count;

    /**
     * @param string $key
     * @param int $count
     */
    public function __construct($key, $count)
    {
        if (!is_string($key)) {
            throw new InvalidArgumentException('Bucket key should be a string.');
        }

        if (!is_int($count)) {
            throw new InvalidArgumentException('Bucket count should be an int.');
        }

        $this->key = $key;
        $this->count = $count;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return int
     */
    public function getCount(): int
    {
        return $this->count;
    }
}
