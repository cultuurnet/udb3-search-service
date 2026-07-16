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
