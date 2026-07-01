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
     * @dataProvider birthdateRangeProvider
     */
    public function it_maps_from_and_to_onto_gte_and_lte(array $from, array $expected): void
    {
        $this->assertEquals($expected, $this->transformer->transform($from, []));
    }

    public function birthdateRangeProvider(): array
    {
        return [
            'from and to' => [
                'from' => [
                    'birthdateRange' => [
                        'from' => '2020-01-01',
                        'to' => '2020-12-31',
                    ],
                ],
                'expected' => [
                    'birthdateRange' => [
                        'gte' => '2020-01-01',
                        'lte' => '2020-12-31',
                    ],
                ],
            ],
            'only from' => [
                'from' => [
                    'birthdateRange' => [
                        'from' => '2020-01-01',
                    ],
                ],
                'expected' => [
                    'birthdateRange' => [
                        'gte' => '2020-01-01',
                    ],
                ],
            ],
            'only to' => [
                'from' => [
                    'birthdateRange' => [
                        'to' => '2020-12-31',
                    ],
                ],
                'expected' => [
                    'birthdateRange' => [
                        'lte' => '2020-12-31',
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider preservedDraftProvider
     */
    public function it_preserves_existing_draft_when_there_is_no_usable_birthdate_range(array $from): void
    {
        $draft = ['name' => 'unchanged'];

        $result = $this->transformer->transform($from, $draft);

        $this->assertSame($draft, $result);
        $this->assertArrayNotHasKey('birthdateRange', $result);
    }

    public function preservedDraftProvider(): array
    {
        return [
            'absent' => [
                'from' => [],
            ],
            'not an array' => [
                'from' => ['birthdateRange' => '2020-01-01 TO 2020-12-31'],
            ],
            'empty array' => [
                'from' => ['birthdateRange' => []],
            ],
        ];
    }
}