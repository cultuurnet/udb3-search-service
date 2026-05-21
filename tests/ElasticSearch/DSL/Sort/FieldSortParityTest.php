<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Sort;

use ONGR\ElasticsearchDSL\Sort\FieldSort as OngrFieldSort;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery as OngrTermQuery;
use CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\TermLevel\TermQuery;
use PHPUnit\Framework\TestCase;

final class FieldSortParityTest extends TestCase
{
    /**
     * @test
     */
    public function it_produces_plain_sort_identically_to_ongr(): void
    {
        $ongr = new OngrFieldSort('created', 'desc');
        $custom = new FieldSort('created', 'desc');

        $this->assertSame(json_encode($ongr->toArray()), json_encode($custom->toArray()));
    }

    /**
     * @test
     */
    public function it_produces_sort_with_extra_parameters_identically_to_ongr(): void
    {
        $ongr = new OngrFieldSort('_geo_distance', 'asc', [
            'geo_point' => ['lat' => 50.0, 'lon' => 4.0],
            'unit' => 'km',
        ]);
        $custom = new FieldSort('_geo_distance', 'asc', [
            'geo_point' => ['lat' => 50.0, 'lon' => 4.0],
            'unit' => 'km',
        ]);

        // Key order differs (ongr appends 'order' last, custom puts it first) so we use assertEquals.
        $this->assertEquals($ongr->toArray(), $custom->toArray());
    }

    /**
     * @test
     * The custom implementation produces the ES8 nested sort format (nested.path + nested.filter)
     * instead of the legacy format (nested_path flat key + nested as raw filter).
     * This test documents that intentional divergence.
     */
    public function it_produces_nested_sort_in_es8_format_unlike_ongr(): void
    {
        $ongrSort = new OngrFieldSort('metadata.recommendationFor.score', 'desc');
        $ongrSort->setNestedFilter(new OngrTermQuery('metadata.recommendationFor.event', 'event-123'));
        $ongrSort->setParameters(['nested_path' => 'metadata.recommendationFor']);

        $customSort = new FieldSort('metadata.recommendationFor.score', 'desc', ['nested_path' => 'metadata.recommendationFor']);
        $customSort->setNestedFilter(new TermQuery('metadata.recommendationFor.event', 'event-123'));

        $ongrOutput = $ongrSort->toArray()['metadata.recommendationFor.score'];
        $customOutput = $customSort->toArray()['metadata.recommendationFor.score'];

        // ongr uses legacy format: nested_path as flat key, nested as raw filter array
        $this->assertArrayHasKey('nested_path', $ongrOutput);
        $this->assertArrayNotHasKey('path', $ongrOutput['nested']);
        $this->assertArrayNotHasKey('filter', $ongrOutput['nested']);

        // custom uses ES8 format: nested_path removed, nested contains path + filter
        $this->assertArrayNotHasKey('nested_path', $customOutput);
        $this->assertArrayHasKey('path', $customOutput['nested']);
        $this->assertArrayHasKey('filter', $customOutput['nested']);
    }
}
