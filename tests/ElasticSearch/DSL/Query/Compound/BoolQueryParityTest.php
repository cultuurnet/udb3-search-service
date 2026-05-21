<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\Compound;

use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery as OngrBoolQuery;
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
        $custom->add(new TermQuery('status', 'available'), BoolQuery::MUST);

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
        $custom->add(new TermQuery('status', 'available'), BoolQuery::FILTER);

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
        $custom->add(new TermQuery('status', 'available'), BoolQuery::SHOULD);

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
        $custom->add(new TermQuery('type', 'event'), BoolQuery::MUST);
        $custom->add(new TermQuery('hidden', 'true'), BoolQuery::MUST_NOT);

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
        $custom->add(new MatchAllQuery(), BoolQuery::MUST);
        $custom->add(new TermQuery('status', 'available'), BoolQuery::MUST);

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
        $custom->add(new MatchAllQuery(), BoolQuery::MUST);
        $custom->add(new TermQuery('status', 'available'), BoolQuery::FILTER);

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
        $custom->add(new MatchAllQuery(), BoolQuery::MUST);
        $custom->add(new TermQuery('field1', 'value1'), BoolQuery::FILTER);
        $custom->add(new TermQuery('field2', 'value2'), BoolQuery::SHOULD);
        $custom->add(new TermQuery('field3', 'value3'), BoolQuery::MUST_NOT);

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
        $custom->add(new TermQuery('field1', 'a'), BoolQuery::FILTER);
        $custom->add(new TermQuery('field2', 'b'), BoolQuery::FILTER);

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
}
