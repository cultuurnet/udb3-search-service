<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use PHPUnit\Framework\TestCase;

final class BirthdateRangeTransformerTest extends TestCase
{
    private BirthdateRangeTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new BirthdateRangeTransformer();
    }

    /**
     * @test
     */
    public function it_maps_from_and_to_onto_gte_and_lte(): void
    {
        $from = [
            'birthdateRange' => [
                'from' => '2020-01-01',
                'to' => '2020-12-31',
            ],
        ];

        $expected = [
            'birthdateRange' => [
                'gte' => '2020-01-01',
                'lte' => '2020-12-31',
            ],
        ];

        $this->assertEquals($expected, $this->transformer->transform($from, []));
    }

    /**
     * @test
     */
    public function it_maps_only_from_onto_gte(): void
    {
        $from = [
            'birthdateRange' => [
                'from' => '2020-01-01',
            ],
        ];

        $expected = [
            'birthdateRange' => [
                'gte' => '2020-01-01',
            ],
        ];

        $this->assertEquals($expected, $this->transformer->transform($from, []));
    }

    /**
     * @test
     */
    public function it_maps_only_to_onto_lte(): void
    {
        $from = [
            'birthdateRange' => [
                'to' => '2020-12-31',
            ],
        ];

        $expected = [
            'birthdateRange' => [
                'lte' => '2020-12-31',
            ],
        ];

        $this->assertEquals($expected, $this->transformer->transform($from, []));
    }

    /**
     * @test
     */
    public function it_preserves_existing_draft_when_birthdate_range_is_absent(): void
    {
        $draft = ['name' => 'unchanged'];

        $this->assertSame($draft, $this->transformer->transform([], $draft));
    }

    /**
     * @test
     */
    public function it_preserves_existing_draft_when_birthdate_range_is_not_an_array(): void
    {
        $from = ['birthdateRange' => '2020-01-01 TO 2020-12-31'];
        $draft = ['name' => 'unchanged'];

        $this->assertSame($draft, $this->transformer->transform($from, $draft));
    }

    /**
     * @test
     */
    public function it_does_not_add_an_empty_birthdate_range_key_when_the_range_is_empty(): void
    {
        $from = ['birthdateRange' => []];
        $draft = ['name' => 'unchanged'];

        $result = $this->transformer->transform($from, $draft);

        $this->assertSame($draft, $result);
        $this->assertArrayNotHasKey('birthdateRange', $result);
    }
}
