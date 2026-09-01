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
    public function it_defaults_has_overnight_stay_to_false_without_a_calendar_type(): void
    {
        $result = $this->transformer->transform([]);

        $this->assertArrayHasKey('hasOvernightStay', $result);
        $this->assertFalse($result['hasOvernightStay']);
    }

    /**
     * @test
     */
    public function it_indexes_has_overnight_stay_false_when_no_sub_event_is_overnight(): void
    {
        $result = $this->transformer->transform($this->multipleCalendar(withHasOvernightStay: false));

        $this->assertFalse($result['hasOvernightStay']);
    }

    /**
     * @test
     */
    public function it_indexes_has_overnight_stay_true_when_a_sub_event_is_overnight(): void
    {
        $result = $this->transformer->transform($this->singleCalendar(withHasOvernightStay: true));

        $this->assertTrue($result['hasOvernightStay']);
    }

    /**
     * A partial overnight event (one sub-event overnight, the rest not) couples on event level: a
     * single overnight sub-event flags the whole offer.
     *
     * @test
     */
    public function it_indexes_has_overnight_stay_true_when_only_one_sub_event_is_overnight(): void
    {
        $result = $this->transformer->transform($this->multipleCalendar(withHasOvernightStay: true));

        $this->assertTrue($result['hasOvernightStay']);
    }

    /**
     * @test
     */
    public function it_ignores_a_sub_event_that_is_explicitly_not_overnight(): void
    {
        $calendar = $this->singleCalendar(withHasOvernightStay: false);
        $calendar['subEvent'][0]['hasOvernightStay'] = false;

        $result = $this->transformer->transform($calendar);

        $this->assertFalse($result['hasOvernightStay']);
    }

    /**
     * Overnight is a sub-event-only flag, so opening-hours-driven calendars never carry it.
     *
     * @test
     * @dataProvider openingHoursCalendarProvider
     */
    public function it_indexes_has_overnight_stay_false_for_opening_hours_calendars(string $type): void
    {
        $result = $this->transformer->transform($this->{$type . 'Calendar'}());

        $this->assertFalse($result['hasOvernightStay']);
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
        $withOvernight = $this->transformer->transform($this->{$type . 'Calendar'}(withHasOvernightStay: true));
        $withoutOvernight = $this->transformer->transform($this->{$type . 'Calendar'}(withHasOvernightStay: false));

        $this->assertTrue($withOvernight['hasOvernightStay']);
        $this->assertFalse($withoutOvernight['hasOvernightStay']);

        $this->assertEquals($withoutOvernight['dateRange'], $withOvernight['dateRange']);
        $this->assertEquals($withoutOvernight['localTimeRange'], $withOvernight['localTimeRange']);

        // Compare only the time-range fields per subEvent; hasOvernightStay differs by design.
        $timeFields = fn (array $se) => array_diff_key($se, ['hasOvernightStay' => true]);
        $this->assertEquals(
            array_map($timeFields, $withoutOvernight['subEvent']),
            array_map($timeFields, $withOvernight['subEvent'])
        );
    }

    /**
     * @test
     */
    public function it_indexes_has_overnight_stay_per_sub_event(): void
    {
        $result = $this->transformer->transform($this->multipleCalendar(withHasOvernightStay: true));

        $this->assertTrue($result['subEvent'][0]['hasOvernightStay']);
        $this->assertFalse($result['subEvent'][1]['hasOvernightStay']);
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
     * @test
     */
    public function it_widens_the_sub_event_period_with_childcare(): void
    {
        $result = $this->transformer->transform($this->singleCalendar(withChildcare: true));

        $this->assertEquals(
            (object) [
                'gte' => '2024-06-01T19:30:00+02:00',
                'lte' => '2024-06-02T08:30:00+02:00',
            ],
            $result['subEvent'][0]['dateRange']
        );
    }

    /**
     * @test
     */
    public function it_widens_the_top_level_date_range_and_local_time_range_with_childcare(): void
    {
        $result = $this->transformer->transform($this->singleCalendarWithChildcare('08:00', '19:00'));

        $this->assertEquals(
            [
                (object) [
                    'gte' => '2024-06-01T08:00:00+02:00',
                    'lte' => '2024-06-01T19:00:00+02:00',
                ],
            ],
            $result['dateRange']
        );
        $this->assertEquals(
            [
                (object) [
                    'gte' => '0800',
                    'lte' => '1900',
                ],
            ],
            $result['localTimeRange']
        );
    }

    /**
     * @test
     */
    public function it_widens_only_the_sub_events_that_have_childcare(): void
    {
        $result = $this->transformer->transform($this->multipleCalendar(withChildcare: true));

        $this->assertEquals(
            (object) [
                'gte' => '2024-06-01T20:00:00+02:00',
                'lte' => '2024-06-02T08:00:00+02:00',
            ],
            $result['subEvent'][0]['dateRange']
        );
        $this->assertEquals(
            (object) [
                'gte' => '2024-06-03T09:00:00+02:00',
                'lte' => '2024-06-03T13:00:00+02:00',
            ],
            $result['subEvent'][1]['dateRange']
        );
    }

    /**
     * @test
     */
    public function it_widens_only_the_start_when_childcare_has_no_end(): void
    {
        $result = $this->transformer->transform($this->singleCalendarWithChildcare('08:00', null));

        $this->assertEquals(
            (object) [
                'gte' => '2024-06-01T08:00:00+02:00',
                'lte' => '2024-06-01T18:00:00+02:00',
            ],
            $result['subEvent'][0]['dateRange']
        );
    }

    /**
     * @test
     */
    public function it_widens_only_the_end_when_childcare_has_no_start(): void
    {
        $result = $this->transformer->transform($this->singleCalendarWithChildcare(null, '19:00'));

        $this->assertEquals(
            (object) [
                'gte' => '2024-06-01T10:00:00+02:00',
                'lte' => '2024-06-01T19:00:00+02:00',
            ],
            $result['subEvent'][0]['dateRange']
        );
    }

    /**
     * @test
     */
    public function it_reads_childcare_times_in_the_local_timezone_of_the_offer(): void
    {
        // 08:00 UTC is 10:00 in Brussels, so childcare at 08:00 local widens the start by two hours.
        $result = $this->transformer->transform([
            'calendarType' => 'single',
            'startDate' => '2024-06-01T08:00:00+00:00',
            'endDate' => '2024-06-01T16:00:00+00:00',
            'subEvent' => [
                [
                    '@type' => 'Event',
                    'startDate' => '2024-06-01T08:00:00+00:00',
                    'endDate' => '2024-06-01T16:00:00+00:00',
                    'childcare' => ['start' => '08:00', 'end' => '19:00'],
                ],
            ],
        ]);

        $this->assertEquals(
            (object) [
                'gte' => '2024-06-01T08:00:00+02:00',
                'lte' => '2024-06-01T19:00:00+02:00',
            ],
            $result['subEvent'][0]['dateRange']
        );
    }

    /**
     * @test
     */
    public function it_ignores_childcare_that_does_not_widen_the_sub_event_period(): void
    {
        $result = $this->transformer->transform($this->singleCalendarWithChildcare('11:00', '17:00'));

        $this->assertEquals(
            (object) [
                'gte' => '2024-06-01T10:00:00+02:00',
                'lte' => '2024-06-01T18:00:00+02:00',
            ],
            $result['subEvent'][0]['dateRange']
        );
    }

    /**
     * @test
     */
    public function it_ignores_a_childcare_time_that_is_not_a_local_time(): void
    {
        $result = $this->transformer->transform($this->singleCalendarWithChildcare('noon', '19:00'));

        $this->assertEquals(
            (object) [
                'gte' => '2024-06-01T10:00:00+02:00',
                'lte' => '2024-06-01T19:00:00+02:00',
            ],
            $result['subEvent'][0]['dateRange']
        );
    }

    /**
     * @test
     * @dataProvider openingHoursCalendarProvider
     */
    public function it_widens_opening_hours_slots_with_childcare(string $type): void
    {
        $result = $this->transformer->transform($this->{$type . 'Calendar'}(withChildcare: true));

        $this->assertEquals(
            [
                (object) [
                    'gte' => '0800',
                    'lte' => '1800',
                ],
            ],
            $result['localTimeRange']
        );
    }

    /**
     * @test
     */
    public function it_widens_the_dates_of_an_opening_hours_slot_with_childcare(): void
    {
        $result = $this->transformer->transform($this->periodicCalendar(withChildcare: true));

        $this->assertEquals(
            (object) [
                'gte' => '2024-06-03T08:00:00+02:00',
                'lte' => '2024-06-03T18:00:00+02:00',
            ],
            $result['subEvent'][0]['dateRange']
        );
    }

    /**
     * @test
     * @dataProvider openingHoursCalendarProvider
     */
    public function it_indexes_has_childcare_per_sub_event_for_opening_hours(string $type): void
    {
        $result = $this->transformer->transform($this->{$type . 'Calendar'}(withChildcare: true));

        $this->assertTrue($result['subEvent'][0]['hasChildcare']);
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
                'hasOvernightStay' => false,
                'hasChildcare' => false,
                'recurringOnDayOfWeek' => [],
                'recurringOnLocalTimeRange' => (object) [],
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
    public function it_omits_days_of_week_below_the_threshold_for_periodic_opening_hours(): void
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
    public function it_only_indexes_days_of_week_that_meet_the_threshold(): void
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
    public function it_excludes_a_day_of_week_pushed_below_the_threshold_by_a_closed_day(): void
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

        // Both days of week occur four times in the range; closing Wednesday 12 drops Wednesday to three,
        // so only Monday — the untouched control — survives the threshold.
        $this->assertSame(['monday'], $result['recurringOnDayOfWeek']);
    }

    /**
     * @test
     */
    public function it_excludes_a_day_of_week_pushed_below_the_threshold_by_an_adjusted_day(): void
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

        // The rolling window yields far more than four Mondays and no other open day of week.
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
    public function it_expands_a_multi_day_sub_event_across_every_day_of_week_it_spans(): void
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
     * @test
     */
    public function it_indexes_no_recurring_local_time_range_without_a_calendar_type(): void
    {
        $result = $this->transformer->transform([]);

        $this->assertEquals((object) [], $result['recurringOnLocalTimeRange']);
    }

    /**
     * @test
     */
    public function it_indexes_the_recurring_local_time_range_of_a_multiple_calendar(): void
    {
        $result = $this->transformer->transform($this->multipleCalendarOn([
            '2024-06-05',
            '2024-06-12',
            '2024-06-19',
            '2024-06-26',
        ]));

        $this->assertEquals(
            (object) ['wednesday' => [['gte' => 1000, 'lt' => 1200]]],
            $result['recurringOnLocalTimeRange']
        );
    }

    /**
     * @test
     */
    public function it_indexes_no_recurring_local_time_range_below_the_threshold(): void
    {
        $result = $this->transformer->transform($this->multipleCalendarOn([
            '2024-06-05',
            '2024-06-12',
            '2024-06-19',
        ]));

        $this->assertEquals((object) [], $result['recurringOnLocalTimeRange']);
    }

    /**
     * @test
     */
    public function it_indexes_the_recurring_local_time_range_of_permanent_opening_hours(): void
    {
        $result = $this->transformer->transform($this->permanentCalendar(false));

        $this->assertEquals(
            (object) ['monday' => [['gte' => 900, 'lt' => 1700]]],
            $result['recurringOnLocalTimeRange']
        );
    }

    /**
     * A child is present for the childcare hours too, so the recurring hours have to cover them.
     * They come along on their own because the sub-events are widened before they are resolved.
     *
     * @test
     */
    public function it_widens_the_recurring_local_time_range_with_childcare(): void
    {
        $result = $this->transformer->transform($this->permanentCalendar(withChildcare: true));

        $this->assertEquals(
            (object) ['monday' => [['gte' => 800, 'lt' => 1800]]],
            $result['recurringOnLocalTimeRange']
        );
    }

    /**
     * A day of week can recur without having hours that recur with it. Three Saturday mornings and
     * three Saturday evenings make six Saturdays, but neither slot is a dependable fixture.
     *
     * @test
     */
    public function it_indexes_a_recurring_day_of_week_without_recurring_hours(): void
    {
        $result = $this->transformer->transform([
            'calendarType' => 'multiple',
            'startDate' => '2024-06-01T10:00:00+02:00',
            'endDate' => '2024-07-06T20:00:00+02:00',
            'subEvent' => [
                $this->subEvent('2024-06-01T10:00:00+02:00', '2024-06-01T12:00:00+02:00'),
                $this->subEvent('2024-06-08T10:00:00+02:00', '2024-06-08T12:00:00+02:00'),
                $this->subEvent('2024-06-15T10:00:00+02:00', '2024-06-15T12:00:00+02:00'),
                $this->subEvent('2024-06-22T18:00:00+02:00', '2024-06-22T20:00:00+02:00'),
                $this->subEvent('2024-06-29T18:00:00+02:00', '2024-06-29T20:00:00+02:00'),
                $this->subEvent('2024-07-06T18:00:00+02:00', '2024-07-06T20:00:00+02:00'),
            ],
        ]);

        $this->assertSame(['saturday'], $result['recurringOnDayOfWeek']);
        $this->assertEquals((object) [], $result['recurringOnLocalTimeRange']);
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
    private function singleCalendar(bool $withHasOvernightStay = false, bool $withChildcare = false): array
    {
        $subEvent = [
            '@type' => 'Event',
            'startDate' => '2024-06-01T20:00:00+02:00',
            'endDate' => '2024-06-02T08:00:00+02:00',
        ];
        if ($withHasOvernightStay) {
            $subEvent['hasOvernightStay'] = true;
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
     * A single calendar running 10:00 to 18:00 local time, with the given childcare bounds.
     *
     * @return array<string, mixed>
     */
    private function singleCalendarWithChildcare(?string $start, ?string $end): array
    {
        $childcare = array_filter(
            ['start' => $start, 'end' => $end],
            static fn (?string $time): bool => $time !== null
        );

        return [
            'calendarType' => 'single',
            'startDate' => '2024-06-01T10:00:00+02:00',
            'endDate' => '2024-06-01T18:00:00+02:00',
            'subEvent' => [
                [
                    '@type' => 'Event',
                    'startDate' => '2024-06-01T10:00:00+02:00',
                    'endDate' => '2024-06-01T18:00:00+02:00',
                    'childcare' => $childcare,
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function multipleCalendar(bool $withHasOvernightStay = false, bool $withChildcare = false): array
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
        if ($withHasOvernightStay) {
            // Only the first sub-event is overnight on purpose: a single overnight sub-event is
            // enough to flag the whole offer.
            $first['hasOvernightStay'] = true;
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
