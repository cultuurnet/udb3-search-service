<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query;

use PHPUnit\Framework\TestCase;

final class MatchAllQueryTest extends TestCase
{
    /**
     * @test
     */
    public function it_produces_match_all_with_empty_object(): void
    {
        $query = new MatchAllQuery();

        $this->assertSame('{"match_all":{}}', json_encode($query->toArray()));
    }
}
