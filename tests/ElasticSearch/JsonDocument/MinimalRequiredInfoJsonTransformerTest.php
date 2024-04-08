<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument;

use PHPUnit\Framework\TestCase;

final class MinimalRequiredInfoJsonTransformerTest extends TestCase
{
    private MinimalRequiredInfoJsonTransformer $transformer;

    protected function setUp(): void
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

    /**
     * @test
     */
    public function it_adds_id_and_type_to_the_draft_but_does_not_mutate_it(): void
    {
        $original = [
            '@id' => 'http://foo.io/events/b1a44077-722b-4a72-9621-50cb8dbb46db',
            '@type' => 'Event',
            'name' => 'Punkfest',
            'foo' => 'bar',
            'lorem' => 'ipsum',
        ];

        $draft = [
            'someOtherProperty' => 'someValue',
        ];

        $expected = [
            'someOtherProperty' => 'someValue',
            '@id' => 'http://foo.io/events/b1a44077-722b-4a72-9621-50cb8dbb46db',
            '@type' => 'Event',
        ];

        $actual = $this->transformer->transform($original, $draft);

        $this->assertEquals($expected, $actual);

        // Make sure the $draft variable was not mutated.
        $this->assertEquals(
            [
                'someOtherProperty' => 'someValue',
            ],
            $draft
        );
    }
}
