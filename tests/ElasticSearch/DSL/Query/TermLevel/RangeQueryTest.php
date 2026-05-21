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
    public function it_produces_range_query_with_gt_and_lt(): void
    {
        $query = new RangeQuery('age', [RangeQuery::GT => 18, RangeQuery::LT => 65]);

        $this->assertSame(
            ['range' => ['age' => ['gt' => 18, 'lt' => 65]]],
            $query->toArray()
        );
    }

    /**
     * @test
     */
    public function it_drops_gt_when_gte_is_also_present(): void
    {
        $query = new RangeQuery('age', [RangeQuery::GTE => 18, RangeQuery::GT => 17]);

        $this->assertSame(
            ['range' => ['age' => ['gte' => 18]]],
            $query->toArray()
        );
    }

    /**
     * @test
     */
    public function it_drops_lt_when_lte_is_also_present(): void
    {
        $query = new RangeQuery('age', [RangeQuery::LTE => 65, RangeQuery::LT => 66]);

        $this->assertSame(
            ['range' => ['age' => ['lte' => 65]]],
            $query->toArray()
        );
    }

    /**
     * @test
     */
    public function it_has_correct_constants(): void
    {
        $this->assertSame('gt', RangeQuery::GT);
        $this->assertSame('gte', RangeQuery::GTE);
        $this->assertSame('lt', RangeQuery::LT);
        $this->assertSame('lte', RangeQuery::LTE);
    }
}
