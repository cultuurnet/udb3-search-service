<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument;

use PHPUnit\Framework\TestCase;

final class JsonLdPolyfillJsonTransformerTest extends TestCase
{
    /**
     * @var array
     *  The JSON-LD that should be poly-filled
     */
    private $given;

    /**
     * @var JsonLdPolyfillJsonTransformer
     */
    private $transformer;

    protected function setUp()
    {
        $this->given = [];
        $this->transformer = new JsonLdPolyfillJsonTransformer();
    }

    /**
     * @test
     */
    public function it_should_remove_metadata_if_set(): void
    {
        $this
            ->given(['metadata' => 'Foo bar bla bla'])
            ->assertReturnedDocumentDoesNotContainKey('metadata');
    }

    /**
     * @test
     */
    public function it_should_not_complain_if_metadata_property_is_not_found(): void
    {
        $this
            ->given(['@type' => 'Event'])
            ->assertReturnedDocumentContains(['@type' => 'Event']);
    }

    private function given(array $given): self
    {
        $this->given = $given;
        return $this;
    }

    private function assertReturnedDocumentContains(array $expected): void
    {
        $actual = $this->transformer->transform([], $this->given);
        $this->assertArrayContainsExpectedKeys($expected, $actual);
    }

    private function assertReturnedDocumentDoesNotContainKey(string $key): void
    {
        $actual = $this->transformer->transform([], $this->given);
        $this->assertArrayNotHasKey($key, $actual);
    }

    private function assertArrayContainsExpectedKeys(array $expected, array $actual): void
    {
        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $actual);
            $this->assertEquals($value, $actual[$key]);
        }
    }
}
