<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use Cake\Chronos\Chronos;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\Calendar\EffectiveOpeningHoursResolver;
use CultuurNet\UDB3\Search\ElasticSearch\SimpleArrayLogger;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerPsrLogger;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;

final class CalendarTransformerTest extends TestCase
{
    private CalendarTransformer $transformer;

    protected function setUp(): void
    {
        // Fixed "now" so permanent calendars generate a deterministic rolling window.
        Chronos::setTestNow(Chronos::createFromFormat(DateTimeInterface::ATOM, '2024-06-01T12:00:00+02:00'));

        $logger = new JsonTransformerPsrLogger(new SimpleArrayLogger());
        $this->transformer = new CalendarTransformer(
            $logger,
            new EffectiveOpeningHoursResolver($logger)
        );
    }

    protected function tearDown(): void
    {
        Chronos::setTestNow();
    }

    /**
     * @test
     */
    public function it_defaults_has_overnight_to_false_without_a_calendar_type(): void
    {
        $result = $this->transformer->transform([]);

        $this->assertArrayHasKey('hasOvernight', $result);
        $this->assertFalse($result['hasOvernight']);
    }

    /**
     * @test
     */
    public function it_indexes_has_overnight_false_when_no_sub_event_is_overnight(): void
    {
        $result = $this->transformer->transform($this->multipleCalendar(withOvernight: false));

        $this->assertFalse($result['hasOvernight']);
    }

    /**
     * @test
     */
    public function it_indexes_has_overnight_true_when_a_sub_event_is_overnight(): void
    {
        $result = $this->transformer->transform($this->singleCalendar(withOvernight: true));

        $this->assertTrue($result['hasOvernight']);
    }

    /**
     * A partial overnight event (one sub-event overnight, the rest not) couples on event level: a
     * single overnight sub-event flags the whole offer.
     *
     * @test
     */
    public function it_indexes_has_overnight_true_when_only_one_sub_event_is_overnight(): void
    {
        $result = $this->transformer->transform($this->multipleCalendar(withOvernight: true));

        $this->assertTrue($result['hasOvernight']);
    }

    /**
     * @test
     */
    public function it_ignores_a_sub_event_that_is_explicitly_not_overnight(): void
    {
        $calendar = $this->singleCalendar(withOvernight: false);
        $calendar['subEvent'][0]['overnight'] = false;

        $result = $this->transformer->transform($calendar);

        $this->assertFalse($result['hasOvernight']);
    }

    /**
     * Overnight is a sub-event-only flag, so opening-hours-driven calendars never carry it.
     *
     * @test
     * @dataProvider openingHoursCalendarProvider
     */
    public function it_indexes_has_overnight_false_for_opening_hours_calendars(string $type): void
    {
        $result = $this->transformer->transform($this->{$type . 'Calendar'}());

        $this->assertFalse($result['hasOvernight']);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public function openingHoursCalendarProvider(): array
    {
        return [
            'periodic' => ['periodic'],
            'permanent' => ['permanent'],
        ];
    }

    /**
     * The overnight flag must never influence the effective time of the activity. The generated
     * dateRange/localTimeRange/subEvent output must therefore be identical whether or not a
     * sub-event is flagged as overnight.
     *
     * @test
     * @dataProvider subEventCalendarProvider
     */
    public function it_does_not_let_overnight_affect_the_effective_time(string $type): void
    {
        $withOvernight = $this->transformer->transform($this->{$type . 'Calendar'}(withOvernight: true));
        $withoutOvernight = $this->transformer->transform($this->{$type . 'Calendar'}(withOvernight: false));

        $this->assertTrue($withOvernight['hasOvernight']);
        $this->assertFalse($withoutOvernight['hasOvernight']);

        $this->assertEquals($withoutOvernight['dateRange'], $withOvernight['dateRange']);
        $this->assertEquals($withoutOvernight['localTimeRange'], $withOvernight['localTimeRange']);

        // Compare only the time-range fields per subEvent; hasOvernight differs by design.
        $timeFields = fn (array $se) => array_diff_key($se, ['hasOvernight' => true]);
        $this->assertEquals(
            array_map($timeFields, $withoutOvernight['subEvent']),
            array_map($timeFields, $withOvernight['subEvent'])
        );
    }

    /**
     * @test
     */
    public function it_indexes_has_overnight_per_sub_event(): void
    {
        $result = $this->transformer->transform($this->multipleCalendar(withOvernight: true));

        $this->assertTrue($result['subEvent'][0]['hasOvernight']);
        $this->assertFalse($result['subEvent'][1]['hasOvernight']);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public function subEventCalendarProvider(): array
    {
        return [
            'single' => ['single'],
            'multiple' => ['multiple'],
        ];
    }

    /**
     * @test
     */
    public function it_defaults_has_childcare_to_false_without_a_calendar_type(): void
    {
        $result = $this->transformer->transform([]);

        $this->assertArrayHasKey('hasChildcare', $result);
        $this->assertFalse($result['hasChildcare']);
    }

    /**
     * @test
     */
    public function it_indexes_has_childcare_false_when_no_sub_event_or_opening_hour_has_childcare(): void
    {
        $result = $this->transformer->transform($this->multipleCalendar(withChildcare: false));

        $this->assertFalse($result['hasChildcare']);
    }

    /**
     * @test
     */
    public function it_indexes_has_childcare_true_when_a_sub_event_has_a_childcare_range(): void
    {
        $result = $this->transformer->transform($this->multipleCalendar(withChildcare: true));

        $this->assertTrue($result['hasChildcare']);
    }

    /**
     * @test
     */
    public function it_indexes_has_childcare_true_when_an_opening_hour_has_a_childcare_range(): void
    {
        $result = $this->transformer->transform($this->periodicCalendar(withChildcare: true));

        $this->assertTrue($result['hasChildcare']);
    }

    /**
     * @test
     */
    public function it_indexes_has_childcare_true_for_permanent_opening_hours_with_childcare(): void
    {
        $result = $this->transformer->transform($this->permanentCalendar(withChildcare: true));

        $this->assertTrue($result['hasChildcare']);
    }

    /**
     * Childcare hours relate to a service before/after the activity and must not influence the
     * effective time. The generated dateRange/localTimeRange and the time-range fields of each
     * subEvent must therefore be identical whether or not childcare is configured.
     *
     * @test
     * @dataProvider calendarProvider
     */
    public function it_does_not_let_childcare_affect_the_effective_time(string $type): void
    {
        $withChildcare = $this->transformer->transform($this->{$type . 'Calendar'}(withChildcare: true));
        $withoutChildcare = $this->transformer->transform($this->{$type . 'Calendar'}(withChildcare: false));

        $this->assertTrue($withChildcare['hasChildcare']);
        $this->assertFalse($withoutChildcare['hasChildcare']);

        $this->assertEquals($withoutChildcare['dateRange'], $withChildcare['dateRange']);
        $this->assertEquals($withoutChildcare['localTimeRange'], $withChildcare['localTimeRange']);

        // Compare only the time-range fields per subEvent; hasChildcare differs by design.
        $timeFields = fn (array $se) => array_diff_key($se, ['hasChildcare' => true]);
        $this->assertEquals(
            array_map($timeFields, $withoutChildcare['subEvent']),
            array_map($timeFields, $withChildcare['subEvent'])
        );
    }

    /**
     * @test
     */
    public function it_indexes_has_childcare_per_sub_event(): void
    {
        $result = $this->transformer->transform($this->multipleCalendar(withChildcare: true));

        $this->assertFalse($result['subEvent'][0]['hasChildcare']);
        $this->assertTrue($result['subEvent'][1]['hasChildcare']);
    }

    /**
     * @test
     */
    public function it_returns_only_defaults_without_a_calendar_type(): void
    {
        $result = $this->transformer->transform([]);

        $this->assertEquals(
            [
                'status' => 'Available',
                'bookingAvailability' => 'Available',
                'hasOvernight' => false,
                'hasChildcare' => false,
                'recurringOnDayOfWeek' => [],
            ],
            $result
        );
    }

    /**
     * @test
     * @dataProvider calendarProvider
     */
    public function it_always_emits_recurring_on_day_of_week(string $calendarType): void
    {
        $method = $calendarType . 'Calendar';
        $result = $this->transformer->transform($this->{$method}(false));

        $this->assertArrayHasKey('recurringOnDayOfWeek', $result);
        $this->assertIsArray($result['recurringOnDayOfWeek']);
    }

    /**
     * @test
     */
    public function it_indexes_empty_recurring_on_day_of_week_for_single_calendars(): void
    {
        $result = $this->transformer->transform($this->singleCalendar(false));

        $this->assertSame([], $result['recurringOnDayOfWeek']);
    }

    /**
     * @test
     */
    public function it_indexes_empty_recurring_on_day_of_week_for_a_multiple_calendar_below_the_threshold(): void
    {
        // One Saturday and one Sunday sub-event, both far below the threshold of 4.
        $result = $this->transformer->transform($this->multipleCalendar(false));

        $this->assertSame([], $result['recurringOnDayOfWeek']);
    }

    /**
     * @test
     */
    public function it_indexes_empty_recurring_on_day_of_week_for_periodic_calendars_without_opening_hours(): void
    {
        $result = $this->transformer->transform([
            'calendarType' => 'periodic',
            'startDate' => '2024-06-03T00:00:00+02:00',
            'endDate' => '2024-06-07T23:59:59+02:00',
        ]);

        $this->assertSame([], $result['recurringOnDayOfWeek']);
    }

    /**
     * @test
     */
    public function it_omits_weekdays_below_the_threshold_for_periodic_opening_hours(): void
    {
        $result = $this->transformer->transform([
            'calendarType' => 'periodic',
            'startDate' => '2024-06-03T00:00:00+02:00',
            'endDate' => '2024-06-07T23:59:59+02:00',
            'openingHours' => [
                [
                    'dayOfWeek' => ['monday', 'wednesday'],
                    'opens' => '08:30',
                    'closes' => '17:00',
                ],
            ],
        ]);

        // Monday and Wednesday each occur only once in this short range, below the threshold of 4.
        $this->assertSame([], $result['recurringOnDayOfWeek']);
    }

    /**
     * @test
     */
    public function it_only_indexes_weekdays_that_meet_the_threshold(): void
    {
        $result = $this->transformer->transform([
            'calendarType' => 'periodic',
            'startDate' => '2024-06-03T00:00:00+02:00',
            'endDate' => '2024-06-25T23:59:59+02:00',
            'openingHours' => [
                [
                    'dayOfWeek' => ['monday', 'wednesday'],
                    'opens' => '08:30',
                    'closes' => '17:00',
                ],
            ],
        ]);

        // Four Mondays (03, 10, 17, 24) meet the threshold; only three Wednesdays (05, 12, 19) do not.
        $this->assertSame(['monday'], $result['recurringOnDayOfWeek']);
    }

    /**
     * @test
     */
    public function it_excludes_a_weekday_pushed_below_the_threshold_by_a_closed_day(): void
    {
        $result = $this->transformer->transform([
            'calendarType' => 'periodic',
            'startDate' => '2024-06-03T00:00:00+02:00',
            'endDate' => '2024-06-26T23:59:59+02:00',
            'openingHours' => [
                [
                    'dayOfWeek' => ['monday', 'wednesday'],
                    'opens' => '08:30',
                    'closes' => '17:00',
                ],
            ],
            'openingHoursClosedDays' => [
                ['startDate' => '2024-06-12', 'endDate' => '2024-06-12'],
            ],
        ]);

        // Both weekdays occur four times in the range; closing Wednesday 12 drops Wednesday to three,
        // so only Monday — the untouched control — survives the threshold.
        $this->assertSame(['monday'], $result['recurringOnDayOfWeek']);
    }

    /**
     * @test
     */
    public function it_excludes_a_weekday_pushed_below_the_threshold_by_an_adjusted_day(): void
    {
        $result = $this->transformer->transform([
            'calendarType' => 'periodic',
            'startDate' => '2024-06-03T00:00:00+02:00',
            'endDate' => '2024-06-26T23:59:59+02:00',
            'openingHours' => [
                [
                    'dayOfWeek' => ['monday', 'wednesday'],
                    'opens' => '08:30',
                    'closes' => '17:00',
                ],
            ],
            'openingHoursAdjustedDays' => [
                [
                    'startDate' => '2024-06-12',
                    'endDate' => '2024-06-12',
                    'openingHours' => [
                        [
                            'dayOfWeek' => ['saturday'],
                            'opens' => '10:00',
                            'closes' => '14:00',
                        ],
                    ],
                ],
            ],
        ]);

        // The adjusted entry on Wednesday 12 only opens on Saturday, closing that Wednesday and dropping
        // Wednesday to three; Monday is untouched, so only Monday survives the threshold.
        $this->assertSame(['monday'], $result['recurringOnDayOfWeek']);
    }

    /**
     * @test
     */
    public function it_indexes_recurring_on_day_of_week_for_permanent_opening_hours_using_the_rolling_window(): void
    {
        $result = $this->transformer->transform($this->permanentCalendar(false));

        // The rolling window yields far more than four Mondays and no other open weekday.
        $this->assertSame(['monday'], $result['recurringOnDayOfWeek']);
    }

    /**
     * @test
     */
    public function it_indexes_recurring_on_day_of_week_from_the_sub_events_of_a_recurring_multiple_calendar(): void
    {
        // Four Wednesdays (05, 12, 19, 26 June 2024) — a weekly recurring event that reaches the threshold.
        $result = $this->transformer->transform($this->multipleCalendarOn([
            '2024-06-05',
            '2024-06-12',
            '2024-06-19',
            '2024-06-26',
        ]));

        $this->assertSame(['wednesday'], $result['recurringOnDayOfWeek']);
    }

    /**
     * @test
     */
    public function it_expands_a_multi_day_sub_event_across_every_weekday_it_spans(): void
    {
        // Four weekend-long sub-events (Friday to Sunday), so each of Friday, Saturday and Sunday
        // is covered four times and reaches the threshold.
        $result = $this->transformer->transform([
            'calendarType' => 'multiple',
            'startDate' => '2024-06-07T18:00:00+02:00',
            'endDate' => '2024-06-30T23:00:00+02:00',
            'subEvent' => [
                $this->subEvent('2024-06-07T18:00:00+02:00', '2024-06-09T23:00:00+02:00'),
                $this->subEvent('2024-06-14T18:00:00+02:00', '2024-06-16T23:00:00+02:00'),
                $this->subEvent('2024-06-21T18:00:00+02:00', '2024-06-23T23:00:00+02:00'),
                $this->subEvent('2024-06-28T18:00:00+02:00', '2024-06-30T23:00:00+02:00'),
            ],
        ]);

        $this->assertSame(['friday', 'saturday', 'sunday'], $result['recurringOnDayOfWeek']);
    }

    /**
     * @test
     */
    public function it_counts_a_multiple_calendar_date_once_even_with_several_sub_events(): void
    {
        // Three distinct Saturdays, one of them holding two sub-events (two slots). Counting days and
        // not slots leaves three Saturdays — below the threshold — so the duplicate cannot inflate it.
        $result = $this->transformer->transform([
            'calendarType' => 'multiple',
            'startDate' => '2024-06-01T10:00:00+02:00',
            'endDate' => '2024-06-15T20:00:00+02:00',
            'subEvent' => [
                $this->subEvent('2024-06-01T10:00:00+02:00', '2024-06-01T12:00:00+02:00'),
                $this->subEvent('2024-06-01T18:00:00+02:00', '2024-06-01T20:00:00+02:00'),
                $this->subEvent('2024-06-08T10:00:00+02:00', '2024-06-08T12:00:00+02:00'),
                $this->subEvent('2024-06-15T10:00:00+02:00', '2024-06-15T12:00:00+02:00'),
            ],
        ]);

        $this->assertSame([], $result['recurringOnDayOfWeek']);
    }

    /**
     * @param list<string> $dates
     *   A list of Y-m-d dates, each turned into a single-day sub-event at a fixed time of day.
     * @return array<string, mixed>
     */
    private function multipleCalendarOn(array $dates): array
    {
        $subEvents = array_map(
            fn (string $date): array => $this->subEvent($date . 'T10:00:00+02:00', $date . 'T12:00:00+02:00'),
            $dates
        );

        return [
            'calendarType' => 'multiple',
            'startDate' => $subEvents[0]['startDate'],
            'endDate' => $subEvents[count($subEvents) - 1]['endDate'],
            'subEvent' => $subEvents,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function subEvent(string $startDate, string $endDate): array
    {
        return [
            '@type' => 'Event',
            'startDate' => $startDate,
            'endDate' => $endDate,
        ];
    }

    /**
     * @return array<string, array{0: string}>
     */
    public function calendarProvider(): array
    {
        return [
            'single' => ['single'],
            'multiple' => ['multiple'],
            'periodic' => ['periodic'],
            'permanent' => ['permanent'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function singleCalendar(bool $withOvernight = false, bool $withChildcare = false): array
    {
        $subEvent = [
            '@type' => 'Event',
            'startDate' => '2024-06-01T20:00:00+02:00',
            'endDate' => '2024-06-02T08:00:00+02:00',
        ];
        if ($withOvernight) {
            $subEvent['overnight'] = true;
        }
        if ($withChildcare) {
            $subEvent['childcare'] = ['start' => '19:30', 'end' => '08:30'];
        }

        return [
            'calendarType' => 'single',
            'startDate' => '2024-06-01T20:00:00+02:00',
            'endDate' => '2024-06-02T08:00:00+02:00',
            'subEvent' => [$subEvent],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function multipleCalendar(bool $withOvernight = false, bool $withChildcare = false): array
    {
        $first = [
            '@type' => 'Event',
            'startDate' => '2024-06-01T20:00:00+02:00',
            'endDate' => '2024-06-02T08:00:00+02:00',
        ];
        $second = [
            '@type' => 'Event',
            'startDate' => '2024-06-03T10:00:00+02:00',
            'endDate' => '2024-06-03T12:00:00+02:00',
        ];
        if ($withOvernight) {
            // Only the first sub-event is overnight on purpose: a single overnight sub-event is
            // enough to flag the whole offer.
            $first['overnight'] = true;
        }
        if ($withChildcare) {
            // Only one of the sub-events carries childcare on purpose: a single configured
            // range is enough to flag the whole offer.
            $second['childcare'] = ['start' => '09:00', 'end' => '13:00'];
        }

        return [
            'calendarType' => 'multiple',
            'startDate' => '2024-06-01T20:00:00+02:00',
            'endDate' => '2024-06-03T12:00:00+02:00',
            'subEvent' => [$first, $second],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function periodicCalendar(bool $withChildcare = false): array
    {
        $openingHour = [
            'dayOfWeek' => ['monday', 'wednesday'],
            'opens' => '08:30',
            'closes' => '17:00',
        ];
        if ($withChildcare) {
            $openingHour['childcare'] = ['start' => '08:00', 'end' => '18:00'];
        }

        return [
            'calendarType' => 'periodic',
            'startDate' => '2024-06-03T00:00:00+02:00',
            'endDate' => '2024-06-07T23:59:59+02:00',
            'openingHours' => [$openingHour],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function permanentCalendar(bool $withChildcare = false): array
    {
        $openingHour = [
            'dayOfWeek' => ['monday'],
            'opens' => '09:00',
            'closes' => '17:00',
        ];
        if ($withChildcare) {
            $openingHour['childcare'] = ['start' => '08:00', 'end' => '18:00'];
        }

        return [
            'calendarType' => 'permanent',
            'openingHours' => [$openingHour],
        ];
    }
}
