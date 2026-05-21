<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\Aggregation\Bucketing\TermsAggregation;
use CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\MatchAllQuery;
use CultuurNet\UDB3\Search\ElasticSearch\DSL\Sort\FieldSort;
use ONGR\ElasticsearchDSL\Aggregation\Bucketing\TermsAggregation as OngrTermsAggregation;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery as OngrBoolQuery;
use ONGR\ElasticsearchDSL\Query\MatchAllQuery as OngrMatchAllQuery;
use ONGR\ElasticsearchDSL\Search as OngrSearch;
use ONGR\ElasticsearchDSL\Sort\FieldSort as OngrFieldSort;
use PHPUnit\Framework\TestCase;

/**
 * Verifies that the custom Search DSL produces output identical to ongr/elasticsearch-dsl
 * for the subset of features used in this codebase.
 *
 * Intentional omissions vs ongr Search (not used in this codebase):
 *   - post filters, highlights, suggests
 *   - track_total_hits, _source, scroll, stored_fields, script_fields
 *   - URI params, min_score, search_after, indices_boost
 */
final class SearchParityTest extends TestCase
{
    /**
     * @test
     */
    public function it_produces_the_same_output_for_a_single_query(): void
    {
        $ongr = new OngrSearch();
        $ongr->setFrom(0);
        $ongr->setSize(10);
        $ongr->addQuery(new OngrMatchAllQuery(), OngrBoolQuery::MUST);

        $custom = new Search();
        $custom->setFrom(0);
        $custom->setSize(10);
        $custom->addQuery(new MatchAllQuery());

        $this->assertEquals($ongr->toArray(), $custom->toArray());
    }

    /**
     * @test
     */
    public function it_produces_the_same_output_for_a_sort(): void
    {
        $ongr = new OngrSearch();
        $ongr->setFrom(0);
        $ongr->setSize(10);
        $ongr->addQuery(new OngrMatchAllQuery(), OngrBoolQuery::MUST);
        $ongr->addSort(new OngrFieldSort('created', OngrFieldSort::DESC));

        $custom = new Search();
        $custom->setFrom(0);
        $custom->setSize(10);
        $custom->addQuery(new MatchAllQuery());
        $custom->addSort(new FieldSort('created', FieldSort::DESC));

        $this->assertEquals($ongr->toArray(), $custom->toArray());
    }

    /**
     * @test
     */
    public function it_produces_the_same_aggregations_output(): void
    {
        $ongr = new OngrSearch();
        $ongr->addAggregation(new OngrTermsAggregation('types', 'typeIds'));

        $custom = new Search();
        $custom->addAggregation(new TermsAggregation('types', 'typeIds'));

        $ongrResult = $ongr->toArray();
        $customResult = $custom->toArray();

        $this->assertEquals($ongrResult['aggregations'], $customResult['aggregations']);
    }
}
