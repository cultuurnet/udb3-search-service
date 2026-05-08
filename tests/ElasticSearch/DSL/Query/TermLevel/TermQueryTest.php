<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\TermLevel;

use PHPUnit\Framework\TestCase;

final class TermQueryTest extends TestCase
{
    /**
     * @test
     */
    public function it_produces_term_query_with_string_value(): void
    {
        $query = new TermQuery('status', 'available');

        $this->assertSame(['term' => ['status' => 'available']], $query->toArray());
    }

    /**
     * @test
     */
    public function it_produces_term_query_with_boolean_value(): void
    {
        $query = new TermQuery('isDuplicate', true);

        $this->assertSame(['term' => ['isDuplicate' => true]], $query->toArray());
    }

    /**
     * @test
     */
    public function it_produces_term_query_with_integer_value(): void
    {
        $query = new TermQuery('count', 42);

        $this->assertSame(['term' => ['count' => 42]], $query->toArray());
    }
}
