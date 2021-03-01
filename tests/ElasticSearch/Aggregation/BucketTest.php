<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Aggregation;

use PHPUnit\Framework\TestCase;

class BucketTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_a_key_and_count()
    {
        $bucket = new Bucket('key', 10);
        $this->assertEquals('key', $bucket->getKey());
        $this->assertEquals(10, $bucket->getCount());
    }

    /**
     * @test
     */
    public function it_checks_that_the_key_is_a_string()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Bucket key should be a string.');
        new Bucket(true, 10);
    }

    /**
     * @test
     */
    public function it_checks_that_the_count_is_an_int()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Bucket count should be an int.');
        new Bucket('key', '10,000,0000');
    }
}
