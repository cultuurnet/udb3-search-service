<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Offer;

use CultuurNet\UDB3\Search\ElasticSearch\LuceneQueryString;
use CultuurNet\UDB3\Search\Language\Language;
use CultuurNet\UDB3\Search\Limit;
use CultuurNet\UDB3\Search\Region\RegionId;
use CultuurNet\UDB3\Search\SortOrder;
use CultuurNet\UDB3\Search\Start;
use PHPUnit\Framework\TestCase;

final class ES8OfferQueryBuilderTest extends TestCase
{
    private function getPredefinedQueryStringFields(Language ...$languages): array
    {
        if (empty($languages)) {
            $languages = [
                new Language('nl'),
                new Language('fr'),
                new Language('en'),
                new Language('de'),
            ];
        }

        return (new OfferPredefinedQueryStringFields())->getPredefinedFields(...$languages);
    }

    /**
     * @test
     */
    public function it_should_build_a_basic_query_with_pagination_and_a_filter(): void
    {
        $builder = (new ES8OfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withAdvancedQuery(new LuceneQueryString('foo AND bar'));

        $expectedQueryArray = [
            'from' => 30,
            'size' => 10,
            'query' => [
                'bool' => [
                    'must' => [
                        ['match_all' => (object)[]],
                        [
                            'query_string' => [
                                'query' => 'foo AND bar',
                                'fields' => $this->getPredefinedQueryStringFields(),
                            ],
                        ],
                    ],
                ],
            ],
            '_source' => ['@id', '@type', 'originalEncodedJsonLd', 'regions'],
        ];

        $actualQueryArray = $builder->build();

        $this->assertEquals($expectedQueryArray, $actualQueryArray);
    }

    /**
     * @test
     */
    public function it_should_build_a_geoshape_filter_without_type_or_relation(): void
    {
        $builder = (new ES8OfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withRegionFilter(
                'geoshapes',
                'regions',
                new RegionId('gem-leuven')
            );

        $actualQueryArray = $builder->build();

        $filterClauses = $actualQueryArray['query']['bool']['filter'];
        $this->assertCount(1, $filterClauses);

        $geoShapeClause = $filterClauses[0];
        $this->assertArrayHasKey('geo_shape', $geoShapeClause);
        $this->assertArrayHasKey('geo', $geoShapeClause['geo_shape']);

        $geoField = $geoShapeClause['geo_shape']['geo'];
        $this->assertArrayHasKey('indexed_shape', $geoField);
        $this->assertArrayNotHasKey('type', $geoField['indexed_shape']);
        $this->assertArrayNotHasKey('relation', $geoField);

        $indexedShape = $geoField['indexed_shape'];
        $this->assertEquals('gem-leuven', $indexedShape['id']);
        $this->assertEquals('geoshapes', $indexedShape['index']);
        $this->assertEquals('location', $indexedShape['path']);
    }

    /**
     * @test
     */
    public function it_should_build_a_recommendation_score_sort_in_es8_nested_format(): void
    {
        $builder = (new ES8OfferQueryBuilder())
            ->withStartAndLimit(new Start(30), new Limit(10))
            ->withSortByRecommendationScore('6f11ca64-0b8b-45e8-8a99-9673f06935cc', SortOrder::asc());

        $actualQueryArray = $builder->build();

        $this->assertArrayHasKey('sort', $actualQueryArray);
        $sortClauses = $actualQueryArray['sort'];
        $this->assertCount(1, $sortClauses);

        $scoreSort = $sortClauses[0];
        $this->assertArrayHasKey('metadata.recommendationFor.score', $scoreSort);

        $sortValue = $scoreSort['metadata.recommendationFor.score'];
        $this->assertEquals('asc', $sortValue['order']);

        // ES8 format: nested is a sub-object with path + filter, no top-level nested_path
        $this->assertArrayNotHasKey('nested_path', $sortValue);
        $this->assertArrayHasKey('nested', $sortValue);
        $this->assertEquals('metadata.recommendationFor', $sortValue['nested']['path']);
        $this->assertEquals(
            ['term' => ['metadata.recommendationFor.event' => '6f11ca64-0b8b-45e8-8a99-9673f06935cc']],
            $sortValue['nested']['filter']
        );
    }
}
