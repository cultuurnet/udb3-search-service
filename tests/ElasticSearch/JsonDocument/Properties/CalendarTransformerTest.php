<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use Cake\Chronos\Chronos;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\Calendar\EffectiveOpeningHours;
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
        $result = $this->transformer->transform($this->multipleCalendar(false));

        $this->assertFalse($result['hasChildcare']);
    }

    /**
     * @test
     */
    public function it_indexes_has_childcare_true_when_a_sub_event_has_a_childcare_range(): void
    {
        $result = $this->transformer->transform($this->multipleCalendar(true));

        $this->assertTrue($result['hasChildcare']);
    }

    /**
     * @test
     */
    public function it_indexes_has_childcare_true_when_an_opening_hour_has_a_childcare_range(): void
    {
        $result = $this->transformer->transform($this->periodicCalendar(true));

        $this->assertTrue($result['hasChildcare']);
    }

    /**
     * @test
     */
    public function it_indexes_has_childcare_true_for_permanent_opening_hours_with_childcare(): void
    {
        $result = $this->transformer->transform($this->permanentCalendar(true));

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
        $withChildcare = $this->transformer->transform($this->{$type . 'Calendar'}(true));
        $withoutChildcare = $this->transformer->transform($this->{$type . 'Calendar'}(false));

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
        $result = $this->transformer->transform($this->multipleCalendar(true));

        $this->assertFalse($result['subEvent'][0]['hasChildcare']);
        $this->assertTrue($result['subEvent'][1]['hasChildcare']);
    }

    /**
     * @test
     */
    public function it_defaults_day_of_week_hits_to_zero_without_a_calendar_type(): void
    {
        $result = $this->transformer->transform([]);

        $this->assertSame(EffectiveOpeningHours::empty()->dayCounts(), $result['dayOfWeekHits']);
    }

    /**
     * @test
     * @dataProvider calendarProvider
     */
    public function it_always_emits_all_seven_day_of_week_hits(string $calendarType): void
    {
        $method = $calendarType . 'Calendar';
        $result = $this->transformer->transform($this->{$method}(false));

        $this->assertSame(
            ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
            array_keys($result['dayOfWeekHits'])
        );
    }

    /**
     * @test
     */
    public function it_counts_zero_day_of_week_hits_for_single_calendars(): void
    {
        $result = $this->transformer->transform($this->singleCalendar(false));

        $this->assertSame(EffectiveOpeningHours::empty()->dayCounts(), $result['dayOfWeekHits']);
    }

    /**
     * @test
     */
    public function it_counts_day_of_week_hits_from_the_sub_events_of_multiple_calendars(): void
    {
        // Sub-events on Saturday 2024-06-01 and Sunday 2024-06-02.
        $result = $this->transformer->transform($this->multipleCalendar(false));

        $this->assertSame(
            [
                'monday' => 0,
                'tuesday' => 0,
                'wednesday' => 0,
                'thursday' => 0,
                'friday' => 0,
                'saturday' => 1,
                'sunday' => 1,
            ],
            $result['dayOfWeekHits']
        );
    }

    /**
     * @test
     */
    public function it_counts_a_multiple_calendar_date_once_even_with_several_sub_events(): void
    {
        // Two sub-events on the same date (Saturday 2024-06-01) must count as a single day.
        $result = $this->transformer->transform([
            'calendarType' => 'multiple',
            'startDate' => '2024-06-01T10:00:00+02:00',
            'endDate' => '2024-06-01T20:00:00+02:00',
            'subEvent' => [
                [
                    '@type' => 'Event',
                    'startDate' => '2024-06-01T10:00:00+02:00',
                    'endDate' => '2024-06-01T12:00:00+02:00',
                ],
                [
                    '@type' => 'Event',
                    'startDate' => '2024-06-01T18:00:00+02:00',
                    'endDate' => '2024-06-01T20:00:00+02:00',
                ],
            ],
        ]);

        $this->assertSame(1, $result['dayOfWeekHits']['saturday']);
    }

    /**
     * @test
     */
    public function it_counts_zero_day_of_week_hits_for_periodic_calendars_without_opening_hours(): void
    {
        $result = $this->transformer->transform([
            'calendarType' => 'periodic',
            'startDate' => '2024-06-03T00:00:00+02:00',
            'endDate' => '2024-06-07T23:59:59+02:00',
        ]);

        $this->assertSame(EffectiveOpeningHours::empty()->dayCounts(), $result['dayOfWeekHits']);
    }

    /**
     * @test
     */
    public function it_counts_day_of_week_hits_for_periodic_opening_hours(): void
    {
        $result = $this->transformer->transform($this->periodicCalendar(false));

        // Range Mon 2024-06-03 to Fri 2024-06-07, opening hours on Monday and Wednesday.
        $this->assertSame(
            [
                'monday' => 1,
                'tuesday' => 0,
                'wednesday' => 1,
                'thursday' => 0,
                'friday' => 0,
                'saturday' => 0,
                'sunday' => 0,
            ],
            $result['dayOfWeekHits']
        );
    }

    /**
     * @test
     */
    public function it_counts_day_of_week_hits_for_permanent_opening_hours_using_the_rolling_window(): void
    {
        $result = $this->transformer->transform($this->permanentCalendar(false));

        // Rolling window -6/+12 months from the fixed now (2024-06-01): 78 Mondays, no other weekday.
        $this->assertSame(
            [
                'monday' => 78,
                'tuesday' => 0,
                'wednesday' => 0,
                'thursday' => 0,
                'friday' => 0,
                'saturday' => 0,
                'sunday' => 0,
            ],
            $result['dayOfWeekHits']
        );
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
    private function singleCalendar(bool $withChildcare): array
    {
        $subEvent = [
            '@type' => 'Event',
            'startDate' => '2024-06-01T10:00:00+02:00',
            'endDate' => '2024-06-01T18:00:00+02:00',
        ];
        if ($withChildcare) {
            $subEvent['childcare'] = ['start' => '09:30', 'end' => '19:00'];
        }

        return [
            'calendarType' => 'single',
            'startDate' => '2024-06-01T10:00:00+02:00',
            'endDate' => '2024-06-01T18:00:00+02:00',
            'subEvent' => [$subEvent],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function multipleCalendar(bool $withChildcare): array
    {
        $first = [
            '@type' => 'Event',
            'startDate' => '2024-06-01T10:00:00+02:00',
            'endDate' => '2024-06-01T12:00:00+02:00',
        ];
        $second = [
            '@type' => 'Event',
            'startDate' => '2024-06-02T10:00:00+02:00',
            'endDate' => '2024-06-02T12:00:00+02:00',
        ];
        if ($withChildcare) {
            // Only one of the sub-events carries childcare on purpose: a single configured
            // range is enough to flag the whole offer.
            $second['childcare'] = ['start' => '09:00', 'end' => '13:00'];
        }

        return [
            'calendarType' => 'multiple',
            'startDate' => '2024-06-01T10:00:00+02:00',
            'endDate' => '2024-06-02T12:00:00+02:00',
            'subEvent' => [$first, $second],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function periodicCalendar(bool $withChildcare): array
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
    private function permanentCalendar(bool $withChildcare): array
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
