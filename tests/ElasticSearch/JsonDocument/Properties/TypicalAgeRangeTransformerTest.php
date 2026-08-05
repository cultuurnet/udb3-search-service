<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use PHPUnit\Framework\TestCase;

final class TypicalAgeRangeTransformerTest extends TestCase
{
    private TypicalAgeRangeTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new TypicalAgeRangeTransformer();
    }

    /**
     * @test
     * @dataProvider typicalAgeRangeProvider
     */
    public function it_maps_the_age_range_onto_a_gte_lte_range(string $typicalAgeRange, array $expected): void
    {
        $this->assertEquals(
            $expected,
            $this->transformer->transform(['typicalAgeRange' => $typicalAgeRange], [])
        );
    }

    public function typicalAgeRangeProvider(): array
    {
        return [
            'both bounds' => [
                'typicalAgeRange' => '8-12',
                'expected' => [
                    'typicalAgeRange' => ['gte' => 8, 'lte' => 12],
                    'allAges' => false,
                ],
            ],
            'no minimum age' => [
                'typicalAgeRange' => '-4',
                'expected' => [
                    'typicalAgeRange' => ['gte' => 0, 'lte' => 4],
                    'allAges' => false,
                ],
            ],
            'no maximum age' => [
                'typicalAgeRange' => '8-',
                'expected' => [
                    'typicalAgeRange' => ['gte' => 8],
                    'allAges' => false,
                ],
            ],
            'all ages' => [
                'typicalAgeRange' => '-',
                'expected' => [
                    'typicalAgeRange' => ['gte' => 0],
                    'allAges' => true,
                ],
            ],
            // A maximum age of 0 is a real bound, not a missing one, so it has to be indexed as lte.
            // Without it the event reads as "0 and up" and matches every age range.
            'maximum age of zero' => [
                'typicalAgeRange' => '0-0',
                'expected' => [
                    'typicalAgeRange' => ['gte' => 0, 'lte' => 0],
                    'allAges' => false,
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider unusableAgeRangeProvider
     */
    public function it_preserves_the_draft_when_there_is_no_usable_age_range(array $from): void
    {
        $draft = ['name' => 'unchanged'];

        $result = $this->transformer->transform($from, $draft);

        $this->assertSame($draft, $result);
        $this->assertArrayNotHasKey('typicalAgeRange', $result);
        $this->assertArrayNotHasKey('allAges', $result);
    }

    public function unusableAgeRangeProvider(): array
    {
        return [
            'absent' => ['from' => []],
            'not a string' => ['from' => ['typicalAgeRange' => ['gte' => 8]]],
            'wrong separator' => ['from' => ['typicalAgeRange' => '4/10']],
            'empty string' => ['from' => ['typicalAgeRange' => '']],
        ];
    }
}
