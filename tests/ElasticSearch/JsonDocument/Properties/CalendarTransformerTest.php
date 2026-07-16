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
