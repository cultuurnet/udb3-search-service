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
    public function it_should_add_missing_id_to_subEvent_objects(): void
    {
        $this
            ->given(
                [
                    'subEvent' => [
                        [
                            '@type' => 'Event',
                            'startDate' => '2020-01-01T16:00:00+01:00',
                            'endDate' => '2020-01-01T20:00:00+01:00',
                        ],
                        [
                            'id' => 'foo',
                            '@type' => 'Event',
                            'startDate' => '2020-01-02T16:00:00+01:00',
                            'endDate' => '2020-01-02T20:00:00+01:00',
                        ],
                    ],
                ]
            )
            ->assertReturnedDocumentContains(
                [
                    'subEvent' => [
                        [
                            'id' => 0,
                            '@type' => 'Event',
                            'startDate' => '2020-01-01T16:00:00+01:00',
                            'endDate' => '2020-01-01T20:00:00+01:00',
                        ],
                        [
                            'id' => 'foo',
                            '@type' => 'Event',
                            'startDate' => '2020-01-02T16:00:00+01:00',
                            'endDate' => '2020-01-02T20:00:00+01:00',
                        ],
                    ],
                ]
            );
    }

    /**
     * @test
     */
    public function it_should_not_complain_if_subEvent_property_is_not_found(): void
    {
        $this
            ->given(['@type' => 'Event'])
            ->assertReturnedDocumentContains(['@type' => 'Event']);
    }

    /**
     * @test
     */
    public function it_should_not_complain_if_subEvent_property_is_not_an_array(): void
    {
        $this
            ->given(['@type' => 'Event', 'subEvent' => 'foo'])
            ->assertReturnedDocumentContains(['@type' => 'Event', 'subEvent' => 'foo']);
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
