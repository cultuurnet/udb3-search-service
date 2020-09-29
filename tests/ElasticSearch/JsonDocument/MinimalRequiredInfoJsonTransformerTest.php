<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument;

use PHPUnit\Framework\TestCase;

class MinimalRequiredInfoJsonTransformerTest extends TestCase
{
    /**
     * @var MinimalRequiredInfoJsonTransformer
     */
    private $transformer;

    public function setUp(): void
    {
        $this->transformer = new MinimalRequiredInfoJsonTransformer();
    }

    /**
     * @test
     */
    public function it_returns_a_json_document_with_only_a_url_and_type(): void
    {
        $original = [
            '@id' => 'http://foo.io/events/b1a44077-722b-4a72-9621-50cb8dbb46db',
            '@type' => 'Event',
            'name' => 'Punkfest',
            'foo' => 'bar',
            'lorem' => 'ipsum',
        ];

        $expected = [
            '@id' => 'http://foo.io/events/b1a44077-722b-4a72-9621-50cb8dbb46db',
            '@type' => 'Event',
        ];

        $actual = $this->transformer->transform($original);

        $this->assertEquals($expected, $actual);
    }
}
