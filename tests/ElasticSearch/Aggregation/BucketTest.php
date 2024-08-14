<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Aggregation;

use PHPUnit\Framework\TestCase;

final class BucketTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_a_key_and_count(): void
    {
        $bucket = new Bucket('key', 10);
        $this->assertEquals('key', $bucket->getKey());
        $this->assertEquals(10, $bucket->getCount());
    }
}
