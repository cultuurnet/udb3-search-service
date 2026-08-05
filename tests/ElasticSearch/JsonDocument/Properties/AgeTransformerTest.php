<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class AgeTransformerTest extends TestCase
{
    private AgeTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new AgeTransformer(new DateTimeImmutable('2026-08-04T09:00:00+02:00'));
    }

    /**
     * @test
     */
    public function it_derives_the_age_range_from_the_birthdate_range(): void
    {
        $from = [
            'calendarType' => 'single',
            'startDate' => '2017-04-22T10:00:00+00:00',
        ];

        $draft = [
            'birthdateRange' => [
                'gte' => '2010-01-01',
                'lte' => '2010-12-31',
            ],
        ];

        // On 22 April 2017 a child born on 31 December 2010 is 6, one born on 1 January 2010 is 7.
        $this->assertEquals(
            [
                'birthdateRange' => [
                    'gte' => '2010-01-01',
                    'lte' => '2010-12-31',
                ],
                'typicalAgeRange' => [
                    'gte' => 6,
                    'lte' => 7,
                ],
                'allAges' => false,
            ],
            $this->transformer->transform($from, $draft)
        );
    }

    /**
     * @test
     */
    public function it_derives_an_unbounded_birthdate_range_for_an_all_ages_event(): void
    {
        $from = [
            'calendarType' => 'single',
            'startDate' => '2017-04-22T10:00:00+00:00',
        ];

        $draft = [
            'typicalAgeRange' => ['gte' => 0],
            'allAges' => true,
        ];

        // An all ages event covers every birthdate, so it matches every birthdate query.
        $this->assertEquals(
            [
                'typicalAgeRange' => ['gte' => 0],
                'allAges' => true,
                'birthdateRange' => [
                    'gte' => null,
                    'lte' => null,
                ],
            ],
            $this->transformer->transform($from, $draft)
        );
    }

    /**
     * @test
     */
    public function it_derives_the_birthdate_range_from_the_age_range(): void
    {
        $from = [
            'calendarType' => 'single',
            'startDate' => '2017-04-22T10:00:00+00:00',
        ];

        $draft = [
            'typicalAgeRange' => [
                'gte' => 6,
                'lte' => 7,
            ],
            'allAges' => false,
        ];

        // On 22 April 2017 the youngest 6 year old was born on 22 April 2011 and the oldest 7 year
        // old on 23 April 2009.
        $this->assertEquals(
            [
                'typicalAgeRange' => [
                    'gte' => 6,
                    'lte' => 7,
                ],
                'allAges' => false,
                'birthdateRange' => [
                    'gte' => '2009-04-23',
                    'lte' => '2011-04-22',
                ],
            ],
            $this->transformer->transform($from, $draft)
        );
    }

    /**
     * @test
     * @dataProvider referenceDateProvider
     */
    public function it_converts_against_the_start_date_per_calendar_type(
        array $from,
        array $expectedBirthdateRange
    ): void {
        $draft = [
            'typicalAgeRange' => ['gte' => 10, 'lte' => 10],
            'allAges' => false,
        ];

        $result = $this->transformer->transform($from, $draft);

        $this->assertEquals($expectedBirthdateRange, $result['birthdateRange']);
    }

    public function referenceDateProvider(): array
    {
        return [
            'single' => [
                'from' => [
                    'calendarType' => 'single',
                    'startDate' => '2026-06-01T10:00:00+02:00',
                    'endDate' => '2026-06-01T18:00:00+02:00',
                ],
                'expectedBirthdateRange' => [
                    'gte' => '2015-06-02',
                    'lte' => '2016-06-01',
                ],
            ],
            // The top level startDate of a multiple event is its earliest sub-event, so the ages
            // describe the audience of the first occurrence.
            'multiple' => [
                'from' => [
                    'calendarType' => 'multiple',
                    'startDate' => '2026-06-01T10:00:00+02:00',
                    'endDate' => '2028-06-01T18:00:00+02:00',
                ],
                'expectedBirthdateRange' => [
                    'gte' => '2015-06-02',
                    'lte' => '2016-06-01',
                ],
            ],
            // Same for a periodic event that runs for years: the first day of the run wins, not the
            // last one and not today.
            'periodic' => [
                'from' => [
                    'calendarType' => 'periodic',
                    'startDate' => '2026-06-01T00:00:00+02:00',
                    'endDate' => '2030-08-31T23:59:59+02:00',
                ],
                'expectedBirthdateRange' => [
                    'gte' => '2015-06-02',
                    'lte' => '2016-06-01',
                ],
            ],
            'permanent' => [
                'from' => [
                    'calendarType' => 'permanent',
                ],
                'expectedBirthdateRange' => [
                    'gte' => '2015-08-05',
                    'lte' => '2016-08-04',
                ],
            ],
        ];
    }

    /**
     * @test
     */
    public function it_derives_the_age_range_of_a_permanent_event_on_the_indexing_time(): void
    {
        $draft = [
            'birthdateRange' => [
                'gte' => '2010-01-01',
                'lte' => '2010-12-31',
            ],
        ];

        $result = $this->transformer->transform(['calendarType' => 'permanent'], $draft);

        $this->assertEquals(['gte' => 15, 'lte' => 16], $result['typicalAgeRange']);
    }

    /**
     * @test
     */
    public function it_derives_an_open_ended_birthdate_range_without_a_maximum_age(): void
    {
        $from = [
            'calendarType' => 'single',
            'startDate' => '2026-06-01T10:00:00+02:00',
        ];

        $result = $this->transformer->transform(
            $from,
            [
                'typicalAgeRange' => ['gte' => 8],
                'allAges' => false,
            ]
        );

        $this->assertEquals(['lte' => '2018-06-01'], $result['birthdateRange']);
    }

    /**
     * @test
     */
    public function it_derives_a_birthdate_range_up_to_the_start_date_from_a_minimum_age_of_zero(): void
    {
        $from = [
            'calendarType' => 'single',
            'startDate' => '2026-06-01T10:00:00+02:00',
        ];

        $result = $this->transformer->transform(
            $from,
            [
                'typicalAgeRange' => ['gte' => 0, 'lte' => 4],
                'allAges' => false,
            ]
        );

        $this->assertEquals(
            [
                'gte' => '2021-06-02',
                'lte' => '2026-06-01',
            ],
            $result['birthdateRange']
        );
    }

    /**
     * @test
     */
    public function it_derives_a_birthdate_range_against_a_start_date_on_29_february(): void
    {
        $from = [
            'calendarType' => 'single',
            'startDate' => '2024-02-29T10:00:00+01:00',
        ];

        $result = $this->transformer->transform(
            $from,
            [
                'typicalAgeRange' => ['gte' => 10, 'lte' => 10],
                'allAges' => false,
            ]
        );

        // A child born on 1 March 2013 is still 10 on 29 February 2024 while one born a day earlier
        // is already 11, and a child born on 28 February 2014 has only just turned 10.
        $this->assertEquals(
            [
                'gte' => '2013-03-01',
                'lte' => '2014-02-28',
            ],
            $result['birthdateRange']
        );
    }

    /**
     * @test
     */
    public function it_derives_an_age_range_against_a_start_date_on_29_february(): void
    {
        $from = [
            'calendarType' => 'single',
            'startDate' => '2024-02-29T10:00:00+01:00',
        ];

        $result = $this->transformer->transform(
            $from,
            [
                'birthdateRange' => [
                    'gte' => '2013-03-01',
                    'lte' => '2014-02-28',
                ],
            ]
        );

        $this->assertEquals(['gte' => 10, 'lte' => 10], $result['typicalAgeRange']);
    }

    /**
     * @test
     */
    public function it_derives_a_birthdate_range_from_a_maximum_age_of_zero(): void
    {
        $from = [
            'calendarType' => 'single',
            'startDate' => '2026-06-01T10:00:00+02:00',
        ];

        $result = $this->transformer->transform(
            $from,
            [
                'typicalAgeRange' => ['gte' => 0, 'lte' => 0],
                'allAges' => false,
            ]
        );

        // Everyone who has not had their first birthday yet on 1 June 2026.
        $this->assertEquals(
            [
                'gte' => '2025-06-02',
                'lte' => '2026-06-01',
            ],
            $result['birthdateRange']
        );
    }

    /**
     * @test
     */
    public function it_uses_the_start_date_as_it_reads_at_its_own_utc_offset(): void
    {
        $from = [
            'calendarType' => 'single',
            'startDate' => '2026-01-01T00:30:00+01:00',
        ];

        $result = $this->transformer->transform(
            $from,
            [
                'typicalAgeRange' => ['gte' => 6, 'lte' => 6],
                'allAges' => false,
            ]
        );

        // Not 31 December 2025, which is the same moment in UTC.
        $this->assertEquals(
            [
                'gte' => '2019-01-02',
                'lte' => '2020-01-01',
            ],
            $result['birthdateRange']
        );
    }

    /**
     * @test
     */
    public function it_derives_a_birthdate_range_that_still_contains_the_ages_it_came_from(): void
    {
        $from = [
            'calendarType' => 'single',
            'startDate' => '2026-06-01T10:00:00+02:00',
        ];

        $ageRange = ['gte' => 6, 'lte' => 12];

        $withBirthdateRange = $this->transformer->transform(
            $from,
            ['typicalAgeRange' => $ageRange, 'allAges' => false]
        );

        // Converting the derived birthdate range back has to land on the age range it started from,
        // otherwise filtering on ages and filtering on birthdates disagree about the same event.
        $roundTripped = $this->transformer->transform(
            $from,
            ['birthdateRange' => $withBirthdateRange['birthdateRange']]
        );

        $this->assertEquals($ageRange, $roundTripped['typicalAgeRange']);
    }

    /**
     * @test
     * @dataProvider untouchedDraftProvider
     */
    public function it_leaves_the_draft_untouched(array $from, array $draft): void
    {
        $this->assertSame($draft, $this->transformer->transform($from, $draft));
    }

    public function untouchedDraftProvider(): array
    {
        $singleEvent = [
            'calendarType' => 'single',
            'startDate' => '2026-06-01T10:00:00+02:00',
        ];

        $ageRange = [
            'typicalAgeRange' => ['gte' => 6, 'lte' => 7],
            'allAges' => false,
        ];

        $birthdateRange = [
            'birthdateRange' => ['gte' => '2018-01-01', 'lte' => '2018-12-31'],
        ];

        return [
            'no age or birthdate range' => [
                'from' => $singleEvent,
                'draft' => [],
            ],
            'both ranges present' => [
                'from' => $singleEvent,
                'draft' => $ageRange + $birthdateRange,
            ],
            'open ended birthdate range' => [
                'from' => $singleEvent,
                'draft' => ['birthdateRange' => ['lte' => '2018-12-31']],
            ],
            'non numeric age range' => [
                'from' => $singleEvent,
                'draft' => [
                    'typicalAgeRange' => ['gte' => '6', 'lte' => '7'],
                    'allAges' => false,
                ],
            ],
            'inverted birthdate range' => [
                'from' => $singleEvent,
                'draft' => [
                    'birthdateRange' => [
                        'gte' => '2018-12-31',
                        'lte' => '2018-01-01',
                    ],
                ],
            ],
            // Only permanent events legitimately have no startDate.
            'no start date' => [
                'from' => ['calendarType' => 'multiple'],
                'draft' => $ageRange,
            ],
            'unparseable start date' => [
                'from' => [
                    'calendarType' => 'single',
                    'startDate' => 'not a date',
                ],
                'draft' => $ageRange,
            ],
        ];
    }
}
