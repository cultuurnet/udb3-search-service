<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\TermLevel;

use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery as OngrTermQuery;
use PHPUnit\Framework\TestCase;

final class TermQueryParityTest extends TestCase
{
    /**
     * @test
     */
    public function it_produces_term_query_with_string_value_identically_to_ongr(): void
    {
        $ongr = new OngrTermQuery('status', 'available');
        $custom = new TermQuery('status', 'available');

        $this->assertSame(json_encode($ongr->toArray()), json_encode($custom->toArray()));
    }

    /**
     * @test
     */
    public function it_produces_term_query_with_boolean_value_identically_to_ongr(): void
    {
        $ongr = new OngrTermQuery('isDuplicate', true);
        $custom = new TermQuery('isDuplicate', true);

        $this->assertSame(json_encode($ongr->toArray()), json_encode($custom->toArray()));
    }

    /**
     * @test
     */
    public function it_produces_term_query_with_integer_value_identically_to_ongr(): void
    {
        $ongr = new OngrTermQuery('count', 42);
        $custom = new TermQuery('count', 42);

        $this->assertSame(json_encode($ongr->toArray()), json_encode($custom->toArray()));
    }

    /**
     * @test
     */
    public function it_produces_term_query_with_float_value_identically_to_ongr(): void
    {
        $ongr = new OngrTermQuery('price', 9.99);
        $custom = new TermQuery('price', 9.99);

        $this->assertSame(json_encode($ongr->toArray()), json_encode($custom->toArray()));
    }
}
