<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\Joining;

use ONGR\ElasticsearchDSL\Query\Joining\NestedQuery as OngrNestedQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery as OngrTermQuery;
use CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\TermLevel\TermQuery;
use PHPUnit\Framework\TestCase;

final class NestedQueryParityTest extends TestCase
{
    /**
     * @test
     */
    public function it_produces_nested_query_identically_to_ongr(): void
    {
        $ongr = new OngrNestedQuery('subEvent', new OngrTermQuery('subEvent.status', 'available'));
        $custom = new NestedQuery('subEvent', new TermQuery('subEvent.status', 'available'));

        $this->assertSame(json_encode($ongr->toArray()), json_encode($custom->toArray()));
    }
}
