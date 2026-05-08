<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\FullText;

use PHPUnit\Framework\TestCase;

final class MatchQueryTest extends TestCase
{
    /**
     * @test
     */
    public function it_produces_match_query(): void
    {
        $query = new MatchQuery('title', 'hello');

        $this->assertSame(['match' => ['title' => 'hello']], $query->toArray());
    }
}
