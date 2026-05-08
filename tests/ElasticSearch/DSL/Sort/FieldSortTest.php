<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Sort;

use CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\TermLevel\TermQuery;
use PHPUnit\Framework\TestCase;

final class FieldSortTest extends TestCase
{
    /**
     * @test
     */
    public function it_produces_a_plain_field_sort(): void
    {
        $sort = new FieldSort('created', 'desc');

        $this->assertSame(['created' => ['order' => 'desc']], $sort->toArray());
    }

    /**
     * @test
     */
    public function it_includes_extra_parameters_in_plain_sort(): void
    {
        $sort = new FieldSort('_geo_distance', 'asc', [
            'geo_point' => ['lat' => 50.0, 'lon' => 4.0],
            'unit' => 'km',
        ]);

        $expected = [
            '_geo_distance' => [
                'order' => 'asc',
                'geo_point' => ['lat' => 50.0, 'lon' => 4.0],
                'unit' => 'km',
            ],
        ];

        $this->assertSame($expected, $sort->toArray());
    }

    /**
     * @test
     */
    public function it_produces_nested_sort_when_nested_filter_and_nested_path_are_set(): void
    {
        $sort = new FieldSort('metadata.recommendationFor.score', 'desc');
        $sort->setNestedFilter(new TermQuery('metadata.recommendationFor.event', 'event-123'));
        $sort->setParameters(['nested_path' => 'metadata.recommendationFor']);

        $expected = [
            'metadata.recommendationFor.score' => [
                'order' => 'desc',
                'nested' => [
                    'path' => 'metadata.recommendationFor',
                    'filter' => ['term' => ['metadata.recommendationFor.event' => 'event-123']],
                ],
            ],
        ];

        $this->assertSame($expected, $sort->toArray());
    }

    /**
     * @test
     */
    public function it_does_not_produce_nested_format_when_only_nested_filter_is_set(): void
    {
        $sort = new FieldSort('score', 'asc');
        $sort->setNestedFilter(new TermQuery('some.field', 'value'));

        $result = $sort->toArray();

        $this->assertArrayNotHasKey('nested', $result['score']);
        $this->assertSame('asc', $result['score']['order']);
    }

    /**
     * @test
     */
    public function it_does_not_leave_nested_path_in_flat_parameters(): void
    {
        $sort = new FieldSort('metadata.recommendationFor.score', 'asc');
        $sort->setNestedFilter(new TermQuery('metadata.recommendationFor.event', 'event-abc'));
        $sort->setParameters(['nested_path' => 'metadata.recommendationFor']);

        $result = $sort->toArray();
        $fieldValue = $result['metadata.recommendationFor.score'];

        $this->assertArrayNotHasKey('nested_path', $fieldValue);
    }

    /**
     * @test
     */
    public function it_merges_parameters_via_set_parameters(): void
    {
        $sort = new FieldSort('field', 'asc', ['unit' => 'km']);
        $sort->setParameters(['distance_type' => 'plane']);

        $result = $sort->toArray();

        $this->assertSame('km', $result['field']['unit']);
        $this->assertSame('plane', $result['field']['distance_type']);
    }
}
