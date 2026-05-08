<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\TermLevel;

use PHPUnit\Framework\TestCase;

final class RangeQueryTest extends TestCase
{
    /**
     * @test
     */
    public function it_produces_range_query_with_gte_and_lte(): void
    {
        $query = new RangeQuery('price', [
            RangeQuery::GTE => 10,
            RangeQuery::LTE => 50,
        ]);

        $this->assertSame(
            ['range' => ['price' => ['gte' => 10, 'lte' => 50]]],
            $query->toArray()
        );
    }

    /**
     * @test
     */
    public function it_produces_range_query_with_only_gte(): void
    {
        $query = new RangeQuery('age', [RangeQuery::GTE => 18]);

        $this->assertSame(
            ['range' => ['age' => ['gte' => 18]]],
            $query->toArray()
        );
    }

    /**
     * @test
     */
    public function it_has_correct_constants(): void
    {
        $this->assertSame('gte', RangeQuery::GTE);
        $this->assertSame('lte', RangeQuery::LTE);
    }
}
