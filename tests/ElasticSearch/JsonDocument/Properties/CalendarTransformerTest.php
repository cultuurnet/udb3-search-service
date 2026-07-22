<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use Cake\Chronos\Chronos;
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

        $this->transformer = new CalendarTransformer(
            new JsonTransformerPsrLogger(new SimpleArrayLogger())
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
        $this->assertEquals($withoutOvernight['subEvent'], $withOvernight['subEvent']);
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
