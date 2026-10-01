<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL\Query\FullText;

use ONGR\ElasticsearchDSL\Query\FullText\MatchPhraseQuery as OngrMatchPhraseQuery;
use PHPUnit\Framework\TestCase;

final class MatchPhraseQueryParityTest extends TestCase
{
    /**
     * @test
     */
    public function it_produces_match_phrase_query_identically_to_ongr(): void
    {
        $ongr = new OngrMatchPhraseQuery('description', 'hello world');
        $custom = new MatchPhraseQuery('description', 'hello world');

        $this->assertSame(json_encode($ongr->toArray()), json_encode($custom->toArray()));
    }
}
