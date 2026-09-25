<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\Aggregation\CardinalityAggregation;
use CultuurNet\UDB3\Search\ElasticSearch\DSL\Aggregation\TermsAggregation;
use CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\Compound\BoolClause;
use CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\Compound\BoolQuery;
use CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\MatchAllQuery;
use CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\TermLevel\TermQuery;
use CultuurNet\UDB3\Search\ElasticSearch\DSL\Sort\FieldSort;
use CultuurNet\UDB3\Search\SortOrder;
use ONGR\ElasticsearchDSL\Aggregation\Bucketing\TermsAggregation as OngrTermsAggregation;
use ONGR\ElasticsearchDSL\Aggregation\Metric\CardinalityAggregation as OngrCardinalityAggregation;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery as OngrBoolQuery;
use ONGR\ElasticsearchDSL\Query\MatchAllQuery as OngrMatchAllQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery as OngrTermQuery;
use ONGR\ElasticsearchDSL\Search as OngrSearch;
use ONGR\ElasticsearchDSL\Sort\FieldSort as OngrFieldSort;
use PHPUnit\Framework\TestCase;

final class SearchParityTest extends TestCase
{
    /**
     * @test
     */
    public function it_produces_a_search_with_only_a_query_identically_to_ongr(): void
    {
        $ongrBool = new OngrBoolQuery();
        $ongrBool->add(new OngrMatchAllQuery(), OngrBoolQuery::MUST);
        $ongr = new OngrSearch();
        $ongr->addQuery($ongrBool);
        $ongr->setFrom(0);
        $ongr->setSize(30);

        $bool = new BoolQuery();
        $bool->add(new MatchAllQuery(), BoolClause::Must);
        $custom = new Search(query: $bool, from: 0, size: 30);

        $this->assertSame(json_encode($ongr->toArray()), json_encode($custom->toArray()));
    }

    /**
     * @test
     */
    public function it_produces_a_search_with_filters_sorts_and_aggregations_identically_to_ongr(): void
    {
        $ongrBool = new OngrBoolQuery();
        $ongrBool->add(new OngrMatchAllQuery(), OngrBoolQuery::MUST);
        $ongrBool->add(new OngrTermQuery('status', 'available'), OngrBoolQuery::FILTER);
        $ongrTerms = new OngrTermsAggregation('regions', 'regions.keyword');
        $ongrTerms->addParameter('size', 50);
        $ongrCardinality = new OngrCardinalityAggregation('total');
        $ongrCardinality->setField('productionCollapseValue');
        $ongr = new OngrSearch();
        $ongr->addQuery($ongrBool);
        $ongr->addSort(new OngrFieldSort('created', 'desc'));
        $ongr->addAggregation($ongrTerms);
        $ongr->addAggregation($ongrCardinality);
        $ongr->setFrom(20);
        $ongr->setSize(10);

        $bool = new BoolQuery();
        $bool->add(new MatchAllQuery(), BoolClause::Must);
        $bool->add(new TermQuery('status', 'available'), BoolClause::Filter);
        $custom = new Search(query: $bool, from: 20, size: 10);
        $custom->addSort(new FieldSort(field: 'created', order: SortOrder::Desc));
        $custom->addAggregation(name: 'regions', aggregation: new TermsAggregation(field: 'regions.keyword', size: 50));
        $custom->addAggregation(name: 'total', aggregation: new CardinalityAggregation(field: 'productionCollapseValue'));

        $this->assertSame(json_encode($ongr->toArray()), json_encode($custom->toArray()));
    }
}
