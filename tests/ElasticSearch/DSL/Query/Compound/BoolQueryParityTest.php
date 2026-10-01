<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\Compound;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\Joining\NestedQuery;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery as OngrBoolQuery;
use ONGR\ElasticsearchDSL\Query\Joining\NestedQuery as OngrNestedQuery;
use ONGR\ElasticsearchDSL\Query\MatchAllQuery as OngrMatchAllQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery as OngrTermQuery;
use CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\MatchAllQuery;
use CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\TermLevel\TermQuery;
use PHPUnit\Framework\TestCase;

/**
 * Asserts that the custom BoolQuery produces identical JSON output to the ongr BoolQuery
 * for the clause combinations used in this codebase.
 */
final class BoolQueryParityTest extends TestCase
{
    /**
     * @test
     */
    public function it_collapses_single_must_identically_to_ongr(): void
    {
        $ongr = new OngrBoolQuery();
        $ongr->add(new OngrTermQuery('status', 'available'), OngrBoolQuery::MUST);

        $custom = new BoolQuery();
        $custom->add(new TermQuery('status', 'available'), BoolClause::Must);

        $this->assertSame(json_encode($ongr->toArray()), json_encode($custom->toArray()));
    }

    /**
     * @test
     */
    public function it_preserves_single_filter_clause_identically_to_ongr(): void
    {
        $ongr = new OngrBoolQuery();
        $ongr->add(new OngrTermQuery('status', 'available'), OngrBoolQuery::FILTER);

        $custom = new BoolQuery();
        $custom->add(new TermQuery('status', 'available'), BoolClause::Filter);

        $this->assertSame(json_encode($ongr->toArray()), json_encode($custom->toArray()));
    }

    /**
     * @test
     */
    public function it_preserves_single_should_clause_identically_to_ongr(): void
    {
        $ongr = new OngrBoolQuery();
        $ongr->add(new OngrTermQuery('status', 'available'), OngrBoolQuery::SHOULD);

        $custom = new BoolQuery();
        $custom->add(new TermQuery('status', 'available'), BoolClause::Should);

        $this->assertSame(json_encode($ongr->toArray()), json_encode($custom->toArray()));
    }

    /**
     * @test
     */
    public function it_preserves_must_with_must_not_identically_to_ongr(): void
    {
        $ongr = new OngrBoolQuery();
        $ongr->add(new OngrTermQuery('type', 'event'), OngrBoolQuery::MUST);
        $ongr->add(new OngrTermQuery('hidden', 'true'), OngrBoolQuery::MUST_NOT);

        $custom = new BoolQuery();
        $custom->add(new TermQuery('type', 'event'), BoolClause::Must);
        $custom->add(new TermQuery('hidden', 'true'), BoolClause::MustNot);

        $this->assertSame(json_encode($ongr->toArray()), json_encode($custom->toArray()));
    }

    /**
     * @test
     */
    public function it_produces_multiple_must_clauses_identically_to_ongr(): void
    {
        $ongr = new OngrBoolQuery();
        $ongr->add(new OngrMatchAllQuery(), OngrBoolQuery::MUST);
        $ongr->add(new OngrTermQuery('status', 'available'), OngrBoolQuery::MUST);

        $custom = new BoolQuery();
        $custom->add(new MatchAllQuery(), BoolClause::Must);
        $custom->add(new TermQuery('status', 'available'), BoolClause::Must);

        $this->assertSame(json_encode($ongr->toArray()), json_encode($custom->toArray()));
    }

    /**
     * @test
     */
    public function it_omits_unused_clause_types_identically_to_ongr(): void
    {
        $ongr = new OngrBoolQuery();
        $ongr->add(new OngrMatchAllQuery(), OngrBoolQuery::MUST);
        $ongr->add(new OngrTermQuery('status', 'available'), OngrBoolQuery::FILTER);

        $custom = new BoolQuery();
        $custom->add(new MatchAllQuery(), BoolClause::Must);
        $custom->add(new TermQuery('status', 'available'), BoolClause::Filter);

        $this->assertSame(json_encode($ongr->toArray()), json_encode($custom->toArray()));
    }

    /**
     * @test
     */
    public function it_supports_all_clause_types_identically_to_ongr(): void
    {
        $ongr = new OngrBoolQuery();
        $ongr->add(new OngrMatchAllQuery(), OngrBoolQuery::MUST);
        $ongr->add(new OngrTermQuery('field1', 'value1'), OngrBoolQuery::FILTER);
        $ongr->add(new OngrTermQuery('field2', 'value2'), OngrBoolQuery::SHOULD);
        $ongr->add(new OngrTermQuery('field3', 'value3'), OngrBoolQuery::MUST_NOT);

        $custom = new BoolQuery();
        $custom->add(new MatchAllQuery(), BoolClause::Must);
        $custom->add(new TermQuery('field1', 'value1'), BoolClause::Filter);
        $custom->add(new TermQuery('field2', 'value2'), BoolClause::Should);
        $custom->add(new TermQuery('field3', 'value3'), BoolClause::MustNot);

        $this->assertSame(json_encode($ongr->toArray()), json_encode($custom->toArray()));
    }

    /**
     * @test
     */
    public function it_collects_multiple_queries_under_same_clause_identically_to_ongr(): void
    {
        $ongr = new OngrBoolQuery();
        $ongr->add(new OngrTermQuery('field1', 'a'), OngrBoolQuery::FILTER);
        $ongr->add(new OngrTermQuery('field2', 'b'), OngrBoolQuery::FILTER);

        $custom = new BoolQuery();
        $custom->add(new TermQuery('field1', 'a'), BoolClause::Filter);
        $custom->add(new TermQuery('field2', 'b'), BoolClause::Filter);

        $this->assertSame(json_encode($ongr->toArray()), json_encode($custom->toArray()));
    }

    /**
     * @test
     */
    public function it_produces_empty_bool_identically_to_ongr(): void
    {
        $ongr = new OngrBoolQuery();
        $custom = new BoolQuery();

        $this->assertSame(json_encode($ongr->toArray()), json_encode($custom->toArray()));
    }

    /**
     * @test
     */
    public function it_preserves_single_must_not_clause_identically_to_ongr(): void
    {
        $ongr = new OngrBoolQuery();
        $ongr->add(new OngrTermQuery('status', 'deleted'), OngrBoolQuery::MUST_NOT);

        $custom = new BoolQuery();
        $custom->add(new TermQuery('status', 'deleted'), BoolClause::MustNot);

        $this->assertSame(json_encode($ongr->toArray()), json_encode($custom->toArray()));
    }

    /**
     * @test
     */
    public function it_serializes_nested_bool_query_identically_to_ongr(): void
    {
        $innerOngr = new OngrBoolQuery();
        $innerOngr->add(new OngrTermQuery('status', 'available'), OngrBoolQuery::FILTER);

        $outerOngr = new OngrBoolQuery();
        $outerOngr->add(new OngrMatchAllQuery(), OngrBoolQuery::MUST);
        $outerOngr->add($innerOngr, OngrBoolQuery::FILTER);

        $innerCustom = new BoolQuery();
        $innerCustom->add(new TermQuery('status', 'available'), BoolClause::Filter);

        $outerCustom = new BoolQuery();
        $outerCustom->add(new MatchAllQuery(), BoolClause::Must);
        $outerCustom->add($innerCustom, BoolClause::Filter);

        $this->assertSame(json_encode($outerOngr->toArray()), json_encode($outerCustom->toArray()));
    }

    /**
     * @test
     */
    public function it_preserves_clause_insertion_order_identically_to_ongr(): void
    {
        $ongr = new OngrBoolQuery();
        $ongr->add(new OngrTermQuery('field1', 'value1'), OngrBoolQuery::SHOULD);
        $ongr->add(new OngrTermQuery('field2', 'value2'), OngrBoolQuery::MUST);
        $ongr->add(new OngrTermQuery('field3', 'value3'), OngrBoolQuery::FILTER);
        $ongr->add(new OngrTermQuery('field4', 'value4'), OngrBoolQuery::MUST_NOT);
        $ongr->add(new OngrTermQuery('field5', 'value5'), OngrBoolQuery::SHOULD);

        $custom = new BoolQuery();
        $custom->add(new TermQuery('field1', 'value1'), BoolClause::Should);
        $custom->add(new TermQuery('field2', 'value2'), BoolClause::Must);
        $custom->add(new TermQuery('field3', 'value3'), BoolClause::Filter);
        $custom->add(new TermQuery('field4', 'value4'), BoolClause::MustNot);
        $custom->add(new TermQuery('field5', 'value5'), BoolClause::Should);

        $this->assertSame(json_encode($ongr->toArray()), json_encode($custom->toArray()));
    }

    /**
     * @test
     */
    public function it_serializes_bool_inside_nested_query_identically_to_ongr(): void
    {
        $innerOngr = new OngrBoolQuery();
        $innerOngr->add(new OngrTermQuery('subEvent.status', 'available'), OngrBoolQuery::FILTER);
        $innerOngr->add(new OngrTermQuery('subEvent.bookingAvailability', 'available'), OngrBoolQuery::FILTER);

        $outerOngr = new OngrBoolQuery();
        $outerOngr->add(new OngrMatchAllQuery(), OngrBoolQuery::MUST);
        $outerOngr->add(new OngrNestedQuery('subEvent', $innerOngr), OngrBoolQuery::FILTER);

        $innerCustom = new BoolQuery();
        $innerCustom->add(new TermQuery('subEvent.status', 'available'), BoolClause::Filter);
        $innerCustom->add(new TermQuery('subEvent.bookingAvailability', 'available'), BoolClause::Filter);

        $outerCustom = new BoolQuery();
        $outerCustom->add(new MatchAllQuery(), BoolClause::Must);
        $outerCustom->add(new NestedQuery('subEvent', $innerCustom), BoolClause::Filter);

        $this->assertSame(json_encode($outerOngr->toArray()), json_encode($outerCustom->toArray()));
    }

    /**
     * @test
     */
    public function it_collapses_single_must_bool_inside_nested_query_identically_to_ongr(): void
    {
        $innerOngr = new OngrBoolQuery();
        $innerOngr->add(new OngrTermQuery('subEvent.status', 'available'), OngrBoolQuery::MUST);

        $innerCustom = new BoolQuery();
        $innerCustom->add(new TermQuery('subEvent.status', 'available'), BoolClause::Must);

        $this->assertSame(
            json_encode((new OngrNestedQuery('subEvent', $innerOngr))->toArray()),
            json_encode((new NestedQuery('subEvent', $innerCustom))->toArray())
        );
    }

    /**
     * @test
     */
    public function it_collapses_single_must_bool_inside_bool_identically_to_ongr(): void
    {
        $innerOngr = new OngrBoolQuery();
        $innerOngr->add(new OngrTermQuery('status', 'available'), OngrBoolQuery::MUST);

        $outerOngr = new OngrBoolQuery();
        $outerOngr->add(new OngrMatchAllQuery(), OngrBoolQuery::MUST);
        $outerOngr->add($innerOngr, OngrBoolQuery::FILTER);

        $innerCustom = new BoolQuery();
        $innerCustom->add(new TermQuery('status', 'available'), BoolClause::Must);

        $outerCustom = new BoolQuery();
        $outerCustom->add(new MatchAllQuery(), BoolClause::Must);
        $outerCustom->add($innerCustom, BoolClause::Filter);

        $this->assertSame(json_encode($outerOngr->toArray()), json_encode($outerCustom->toArray()));
    }
}
