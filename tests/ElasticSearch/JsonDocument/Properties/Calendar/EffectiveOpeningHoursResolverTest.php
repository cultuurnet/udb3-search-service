<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\Calendar;

use Cake\Chronos\Chronos;
use CultuurNet\UDB3\Search\ElasticSearch\SimpleArrayLogger;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerPsrLogger;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;

final class EffectiveOpeningHoursResolverTest extends TestCase
{
    private EffectiveOpeningHoursResolver $resolver;

    protected function setUp(): void
    {
        // Fixed "now" so permanent calendars resolve a deterministic rolling window.
        Chronos::setTestNow(Chronos::createFromFormat(DateTimeInterface::ATOM, '2024-06-01T12:00:00+02:00'));

        $this->resolver = new EffectiveOpeningHoursResolver(
            new JsonTransformerPsrLogger(new SimpleArrayLogger())
        );
    }

    protected function tearDown(): void
    {
        Chronos::setTestNow();
    }

    /**
     * @test
     * @dataProvider periodicCalendarProvider
     * @param array<string, mixed> $from
     * @param list<array{0: string, 1: string, 2: string}> $expectedSlots
     *   Each entry is [date (Y-m-d), opens, closes].
     */
    public function it_resolves_effective_opening_hours_for_a_periodic_window(array $from, array $expectedSlots): void
    {
        $actual = array_map(
            static fn (array $slot): array => [
                $slot['date']->format('Y-m-d'),
                $slot['opens'],
                $slot['closes'],
            ],
            $this->resolver->resolve($from)->slots()
        );

        $this->assertSame($expectedSlots, $actual);
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: list<array{0: string, 1: string, 2: string}>}>
     */
    public function periodicCalendarProvider(): array
    {
        return [
            'grouped per weekday' => [
                [
                    'calendarType' => 'periodic',
                    'startDate' => '2024-06-03T00:00:00+02:00',
                    'endDate' => '2024-06-09T23:59:59+02:00',
                    'openingHours' => [
                        [
                            'dayOfWeek' => ['monday', 'wednesday'],
                            'opens' => '08:30',
                            'closes' => '17:00',
                        ],
                    ],
                ],
                // Monday 2024-06-03 and Wednesday 2024-06-05 within the window.
                [
                    ['2024-06-03', '08:30', '17:00'],
                    ['2024-06-05', '08:30', '17:00'],
                ],
            ],
            'multiple slots on the same weekday sorted by opens' => [
                [
                    'calendarType' => 'periodic',
                    'startDate' => '2024-06-03T00:00:00+02:00',
                    'endDate' => '2024-06-03T23:59:59+02:00',
                    'openingHours' => [
                        [
                            'dayOfWeek' => ['monday'],
                            'opens' => '13:00',
                            'closes' => '17:00',
                        ],
                        [
                            'dayOfWeek' => ['monday'],
                            'opens' => '08:30',
                            'closes' => '12:00',
                        ],
                    ],
                ],
                [
                    ['2024-06-03', '08:30', '12:00'],
                    ['2024-06-03', '13:00', '17:00'],
                ],
            ],
            'closed days excluded' => [
                [
                    'calendarType' => 'periodic',
                    'startDate' => '2024-06-03T00:00:00+02:00',
                    'endDate' => '2024-06-12T23:59:59+02:00',
                    'openingHours' => [
                        [
                            'dayOfWeek' => ['monday', 'wednesday'],
                            'opens' => '08:30',
                            'closes' => '17:00',
                        ],
                    ],
                    'openingHoursClosedDays' => [
                        ['startDate' => '2024-06-05', 'endDate' => '2024-06-05'],
                    ],
                ],
                // Mondays 2024-06-03 and 2024-06-10 and Wednesday 2024-06-12 remain; Wednesday 2024-06-05 is closed.
                [
                    ['2024-06-03', '08:30', '17:00'],
                    ['2024-06-10', '08:30', '17:00'],
                    ['2024-06-12', '08:30', '17:00'],
                ],
            ],
            'adjusted opening hours substituted' => [
                [
                    'calendarType' => 'periodic',
                    'startDate' => '2024-06-03T00:00:00+02:00',
                    'endDate' => '2024-06-05T23:59:59+02:00',
                    'openingHours' => [
                        [
                            'dayOfWeek' => ['monday', 'wednesday'],
                            'opens' => '08:30',
                            'closes' => '17:00',
                        ],
                    ],
                    'openingHoursAdjustedDays' => [
                        [
                            'startDate' => '2024-06-03',
                            'endDate' => '2024-06-03',
                            'openingHours' => [
                                [
                                    'dayOfWeek' => ['monday'],
                                    'opens' => '10:00',
                                    'closes' => '14:00',
                                ],
                            ],
                        ],
                    ],
                ],
                // Monday 2024-06-03 uses the adjusted hours; Wednesday 2024-06-05 keeps the regular hours.
                [
                    ['2024-06-03', '10:00', '14:00'],
                    ['2024-06-05', '08:30', '17:00'],
                ],
            ],
            'closed day overrides adjusted day' => [
                [
                    'calendarType' => 'periodic',
                    'startDate' => '2024-06-03T00:00:00+02:00',
                    'endDate' => '2024-06-03T23:59:59+02:00',
                    'openingHours' => [
                        [
                            'dayOfWeek' => ['monday'],
                            'opens' => '08:30',
                            'closes' => '17:00',
                        ],
                    ],
                    'openingHoursAdjustedDays' => [
                        [
                            'startDate' => '2024-06-03',
                            'endDate' => '2024-06-03',
                            'openingHours' => [
                                [
                                    'dayOfWeek' => ['monday'],
                                    'opens' => '10:00',
                                    'closes' => '14:00',
                                ],
                            ],
                        ],
                    ],
                    'openingHoursClosedDays' => [
                        ['startDate' => '2024-06-03', 'endDate' => '2024-06-03'],
                    ],
                ],
                [],
            ],
            'adjusted day without matching weekday hours treated as closed' => [
                [
                    'calendarType' => 'periodic',
                    'startDate' => '2024-06-03T00:00:00+02:00',
                    'endDate' => '2024-06-03T23:59:59+02:00',
                    'openingHours' => [
                        [
                            'dayOfWeek' => ['monday'],
                            'opens' => '08:30',
                            'closes' => '17:00',
                        ],
                    ],
                    'openingHoursAdjustedDays' => [
                        [
                            'startDate' => '2024-06-03',
                            'endDate' => '2024-06-03',
                            'openingHours' => [
                                [
                                    'dayOfWeek' => ['tuesday'],
                                    'opens' => '10:00',
                                    'closes' => '14:00',
                                ],
                            ],
                        ],
                    ],
                ],
                // Monday 2024-06-03 is adjusted, but the adjusted entry only lists Tuesday, so the Monday is closed.
                [],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dayCountsProvider
     * @param array<string, mixed> $from
     * @param array{monday: int, tuesday: int, wednesday: int, thursday: int, friday: int, saturday: int, sunday: int} $expectedDayCounts
     */
    public function it_counts_effective_open_days_per_weekday(array $from, array $expectedDayCounts): void
    {
        $this->assertSame($expectedDayCounts, $this->resolver->resolve($from)->dayCounts());
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: array<string, int>}>
     */
    public function dayCountsProvider(): array
    {
        return [
            'periodic within a single week' => [
                [
                    'calendarType' => 'periodic',
                    'startDate' => '2024-06-03T00:00:00+02:00',
                    'endDate' => '2024-06-09T23:59:59+02:00',
                    'openingHours' => [
                        [
                            'dayOfWeek' => ['monday', 'wednesday', 'friday'],
                            'opens' => '08:30',
                            'closes' => '17:00',
                        ],
                    ],
                ],
                [
                    'monday' => 1,
                    'tuesday' => 0,
                    'wednesday' => 1,
                    'thursday' => 0,
                    'friday' => 1,
                    'saturday' => 0,
                    'sunday' => 0,
                ],
            ],
            'periodic spanning multiple weeks' => [
                [
                    'calendarType' => 'periodic',
                    'startDate' => '2024-06-03T00:00:00+02:00',
                    'endDate' => '2024-06-16T23:59:59+02:00',
                    'openingHours' => [
                        [
                            'dayOfWeek' => ['monday', 'wednesday'],
                            'opens' => '08:30',
                            'closes' => '17:00',
                        ],
                    ],
                ],
                // Two Mondays (03, 10) and two Wednesdays (05, 12) within the fortnight.
                [
                    'monday' => 2,
                    'tuesday' => 0,
                    'wednesday' => 2,
                    'thursday' => 0,
                    'friday' => 0,
                    'saturday' => 0,
                    'sunday' => 0,
                ],
            ],
            'multiple slots on the same weekday count the day once' => [
                [
                    'calendarType' => 'periodic',
                    'startDate' => '2024-06-03T00:00:00+02:00',
                    'endDate' => '2024-06-03T23:59:59+02:00',
                    'openingHours' => [
                        [
                            'dayOfWeek' => ['monday'],
                            'opens' => '08:30',
                            'closes' => '12:00',
                        ],
                        [
                            'dayOfWeek' => ['monday'],
                            'opens' => '13:00',
                            'closes' => '17:00',
                        ],
                    ],
                ],
                [
                    'monday' => 1,
                    'tuesday' => 0,
                    'wednesday' => 0,
                    'thursday' => 0,
                    'friday' => 0,
                    'saturday' => 0,
                    'sunday' => 0,
                ],
            ],
            'closed days are not counted' => [
                [
                    'calendarType' => 'periodic',
                    'startDate' => '2024-06-03T00:00:00+02:00',
                    'endDate' => '2024-06-16T23:59:59+02:00',
                    'openingHours' => [
                        [
                            'dayOfWeek' => ['wednesday'],
                            'opens' => '08:30',
                            'closes' => '17:00',
                        ],
                    ],
                    'openingHoursClosedDays' => [
                        ['startDate' => '2024-06-05', 'endDate' => '2024-06-05'],
                    ],
                ],
                // Wednesday 05 is closed, only Wednesday 12 remains open.
                [
                    'monday' => 0,
                    'tuesday' => 0,
                    'wednesday' => 1,
                    'thursday' => 0,
                    'friday' => 0,
                    'saturday' => 0,
                    'sunday' => 0,
                ],
            ],
            'adjusted day that keeps the day open still counts' => [
                [
                    'calendarType' => 'periodic',
                    'startDate' => '2024-06-03T00:00:00+02:00',
                    'endDate' => '2024-06-05T23:59:59+02:00',
                    'openingHours' => [
                        [
                            'dayOfWeek' => ['monday', 'wednesday'],
                            'opens' => '08:30',
                            'closes' => '17:00',
                        ],
                    ],
                    'openingHoursAdjustedDays' => [
                        [
                            'startDate' => '2024-06-03',
                            'endDate' => '2024-06-03',
                            'openingHours' => [
                                [
                                    'dayOfWeek' => ['monday'],
                                    'opens' => '10:00',
                                    'closes' => '14:00',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'monday' => 1,
                    'tuesday' => 0,
                    'wednesday' => 1,
                    'thursday' => 0,
                    'friday' => 0,
                    'saturday' => 0,
                    'sunday' => 0,
                ],
            ],
            'adjusted day that closes the weekday is not counted' => [
                [
                    'calendarType' => 'periodic',
                    'startDate' => '2024-06-03T00:00:00+02:00',
                    'endDate' => '2024-06-03T23:59:59+02:00',
                    'openingHours' => [
                        [
                            'dayOfWeek' => ['monday'],
                            'opens' => '08:30',
                            'closes' => '17:00',
                        ],
                    ],
                    'openingHoursAdjustedDays' => [
                        [
                            'startDate' => '2024-06-03',
                            'endDate' => '2024-06-03',
                            'openingHours' => [
                                [
                                    'dayOfWeek' => ['tuesday'],
                                    'opens' => '10:00',
                                    'closes' => '14:00',
                                ],
                            ],
                        ],
                    ],
                ],
                // Monday 03 is adjusted, but the adjusted entry only lists Tuesday, so the Monday is closed.
                [
                    'monday' => 0,
                    'tuesday' => 0,
                    'wednesday' => 0,
                    'thursday' => 0,
                    'friday' => 0,
                    'saturday' => 0,
                    'sunday' => 0,
                ],
            ],
        ];
    }

    /**
     * @test
     */
    public function it_returns_an_empty_result_when_there_are_no_opening_hours(): void
    {
        $effectiveOpeningHours = $this->resolver->resolve([
            'calendarType' => 'periodic',
            'startDate' => '2024-06-03T00:00:00+02:00',
            'endDate' => '2024-06-09T23:59:59+02:00',
        ]);

        $this->assertSame([], $effectiveOpeningHours->slots());
        $this->assertSame(EffectiveOpeningHours::empty()->dayCounts(), $effectiveOpeningHours->dayCounts());
    }

    /**
     * @test
     */
    public function it_returns_an_empty_result_for_a_periodic_calendar_without_a_window(): void
    {
        $effectiveOpeningHours = $this->resolver->resolve([
            'calendarType' => 'periodic',
            'openingHours' => [
                [
                    'dayOfWeek' => ['monday'],
                    'opens' => '08:30',
                    'closes' => '17:00',
                ],
            ],
        ]);

        $this->assertSame([], $effectiveOpeningHours->slots());
        $this->assertSame(EffectiveOpeningHours::empty()->dayCounts(), $effectiveOpeningHours->dayCounts());
    }

    /**
     * @test
     */
    public function it_counts_days_across_the_permanent_rolling_window(): void
    {
        $from = [
            'calendarType' => 'permanent',
            'openingHours' => [
                [
                    'dayOfWeek' => ['monday'],
                    'opens' => '09:00',
                    'closes' => '17:00',
                ],
            ],
        ];

        $dayCounts = $this->resolver->resolve($from)->dayCounts();

        // Window is now -6 months (2023-12-01) up to now +12 months (2025-06-01): 78 Mondays, no other weekdays.
        $this->assertSame(78, $dayCounts['monday']);
        $this->assertSame(0, $dayCounts['tuesday']);
        $this->assertSame(0, $dayCounts['sunday']);
    }

    /**
     * @test
     */
    public function it_resolves_a_permanent_window_from_minus_6_to_plus_12_months(): void
    {
        $from = [
            'calendarType' => 'permanent',
            'openingHours' => [
                [
                    'dayOfWeek' => ['monday'],
                    'opens' => '09:00',
                    'closes' => '17:00',
                ],
            ],
        ];

        $slots = $this->resolver->resolve($from)->slots();

        // Window is now -6 months (2023-12-01) up to now +12 months (2025-06-01).
        $dates = array_map(static fn (array $slot): string => $slot['date']->format('Y-m-d'), $slots);

        $this->assertNotEmpty($dates);
        $this->assertSame('2023-12-04', $dates[0]);
        $this->assertSame('2025-05-26', $dates[count($dates) - 1]);
        // Every generated date is a Monday.
        foreach ($slots as $slot) {
            $this->assertSame('Monday', $slot['date']->format('l'));
            $this->assertSame('09:00', $slot['opens']);
            $this->assertSame('17:00', $slot['closes']);
        }
    }
}
