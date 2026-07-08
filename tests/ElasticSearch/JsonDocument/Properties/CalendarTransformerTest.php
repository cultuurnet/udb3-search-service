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
        $result = $this->transformer->transform($this->multipleCalendar(false));

        $this->assertFalse($result['hasOvernight']);
    }

    /**
     * @test
     */
    public function it_indexes_has_overnight_true_when_a_sub_event_is_overnight(): void
    {
        $result = $this->transformer->transform($this->singleCalendar(true));

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
        $result = $this->transformer->transform($this->multipleCalendar(true));

        $this->assertTrue($result['hasOvernight']);
    }

    /**
     * @test
     */
    public function it_ignores_a_sub_event_that_is_explicitly_not_overnight(): void
    {
        $calendar = $this->singleCalendar(false);
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
        $withOvernight = $this->transformer->transform($this->{$type . 'Calendar'}(true));
        $withoutOvernight = $this->transformer->transform($this->{$type . 'Calendar'}(false));

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
     * @return array<string, mixed>
     */
    private function singleCalendar(bool $withOvernight): array
    {
        $subEvent = [
            '@type' => 'Event',
            'startDate' => '2024-06-01T20:00:00+02:00',
            'endDate' => '2024-06-02T08:00:00+02:00',
        ];
        if ($withOvernight) {
            $subEvent['overnight'] = true;
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
    private function multipleCalendar(bool $withOvernight): array
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
    private function periodicCalendar(): array
    {
        return [
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
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function permanentCalendar(): array
    {
        return [
            'calendarType' => 'permanent',
            'openingHours' => [
                [
                    'dayOfWeek' => ['monday'],
                    'opens' => '09:00',
                    'closes' => '17:00',
                ],
            ],
        ];
    }
}
