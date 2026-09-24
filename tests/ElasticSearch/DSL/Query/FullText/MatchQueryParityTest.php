<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\FullText;

use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery as OngrMatchQuery;
use PHPUnit\Framework\TestCase;

final class MatchQueryParityTest extends TestCase
{
    /**
     * @test
     */
    public function it_produces_match_query_identically_to_ongr(): void
    {
        $ongr = new OngrMatchQuery('title', 'hello');
        $custom = new MatchQuery('title', 'hello');

        $this->assertSame(json_encode($ongr->toArray()), json_encode($custom->toArray()));
    }
}
