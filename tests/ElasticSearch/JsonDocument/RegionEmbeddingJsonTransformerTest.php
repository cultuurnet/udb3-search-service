<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument;

use PHPUnit\Framework\TestCase;

final class RegionEmbeddingJsonTransformerTest extends TestCase
{
    private RegionEmbeddingJsonTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new RegionEmbeddingJsonTransformer();
    }

    /**
     * @test
     */
    public function it_should_embed_the_regions_if_the_original_has_one_or_more(): void
    {
        $original = [
            'foo' => 'bar',
            'regions' => [
                'nis-20001',
                'nis-24062C',
                'reg-leuven',
                'nis-24062',
            ],
        ];

        $draft = ['other' => true];

        $expected = [
            'other' => true,
            'regions' => [
                'nis-20001',
                'nis-24062C',
                'reg-leuven',
                'nis-24062',
            ],
        ];

        $actual = $this->transformer->transform($original, $draft);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_embed_an_empty_array_if_no_regions_on_original(): void
    {
        $original = ['foo' => 'bar'];
        $draft = ['other' => true];

        $expected = [
            'other' => true,
            'regions' => [],
        ];

        $actual = $this->transformer->transform($original, $draft);
        $this->assertEquals($expected, $actual);
    }
}
