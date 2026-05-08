<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\FullText;

use PHPUnit\Framework\TestCase;

final class MatchPhraseQueryTest extends TestCase
{
    /**
     * @test
     */
    public function it_produces_match_phrase_query(): void
    {
        $query = new MatchPhraseQuery('description', 'hello world');

        $this->assertSame(['match_phrase' => ['description' => 'hello world']], $query->toArray());
    }
}
