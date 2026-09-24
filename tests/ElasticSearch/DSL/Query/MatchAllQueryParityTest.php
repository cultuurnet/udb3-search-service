<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query;

use ONGR\ElasticsearchDSL\Query\MatchAllQuery as OngrMatchAllQuery;
use PHPUnit\Framework\TestCase;

final class MatchAllQueryParityTest extends TestCase
{
    /**
     * @test
     */
    public function it_produces_match_all_identically_to_ongr(): void
    {
        $ongr = new OngrMatchAllQuery();
        $custom = new MatchAllQuery();

        $this->assertSame(json_encode($ongr->toArray()), json_encode($custom->toArray()));
    }
}
