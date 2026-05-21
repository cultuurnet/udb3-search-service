<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\FullText;

use ONGR\ElasticsearchDSL\Query\FullText\QueryStringQuery as OngrQueryStringQuery;
use PHPUnit\Framework\TestCase;

final class QueryStringQueryParityTest extends TestCase
{
    /**
     * @test
     */
    public function it_produces_query_string_without_parameters_identically_to_ongr(): void
    {
        $ongr = new OngrQueryStringQuery('my query');
        $custom = new QueryStringQuery('my query');

        $this->assertSame(json_encode($ongr->toArray()), json_encode($custom->toArray()));
    }

    /**
     * @test
     */
    public function it_produces_query_string_with_parameters_identically_to_ongr(): void
    {
        $ongr = new OngrQueryStringQuery('foo bar', [
            'fields' => ['title', 'description'],
            'default_operator' => 'AND',
        ]);
        $custom = new QueryStringQuery('foo bar', [
            'fields' => ['title', 'description'],
            'default_operator' => 'AND',
        ]);

        $this->assertSame(json_encode($ongr->toArray()), json_encode($custom->toArray()));
    }
}
