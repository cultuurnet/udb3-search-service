<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\Calendar;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

final class RecurringLocalTimeRangeResolverTest extends TestCase
{
    private const MINIMUM_OCCURRENCES = 4;

    private RecurringLocalTimeRangeResolver $resolver;

    private DateTimeZone $timezone;

    protected function setUp(): void
    {
        $this->resolver = new RecurringLocalTimeRangeResolver(self::MINIMUM_OCCURRENCES);
        $this->timezone = new DateTimeZone('Europe/Brussels');
    }

    /**
     * @test
     */
    public function it_resolves_nothing_without_sub_events(): void
    {
        $this->assertSame([], $this->resolver->resolve([], $this->timezone));
    }

    /**
     * @test
     */
    public function it_resolves_the_hours_of_a_weekly_slot(): void
    {
        $subEvents = $this->weekly('2026-08-05', '10:00', '12:00', 4);

        $this->assertSame(
            ['wednesday' => [['gte' => 1000, 'lt' => 1200]]],
            $this->resolver->resolve($subEvents, $this->timezone)
        );
    }

    /**
     * @test
     */
    public function it_resolves_nothing_below_the_minimum_number_of_occurrences(): void
    {
        $subEvents = $this->weekly('2026-08-05', '10:00', '12:00', 3);

        $this->assertSame([], $this->resolver->resolve($subEvents, $this->timezone));
    }

    /**
     * @test
     */
    public function it_keeps_the_hours_of_every_day_of_week_apart(): void
    {
        $subEvents = array_merge(
            $this->weekly('2026-08-05', '10:00', '12:00', 4),
            $this->weekly('2026-08-01', '14:00', '18:00', 4)
        );

        $this->assertSame(
            [
                'wednesday' => [['gte' => 1000, 'lt' => 1200]],
                'saturday' => [['gte' => 1400, 'lt' => 1800]],
            ],
            $this->resolver->resolve($subEvents, $this->timezone)
        );
    }

    /**
     * Two slots on the same day of week must stay two ranges. One range covering both would wrongly
     * match the lunch break in between.
     *
     * @test
     */
    public function it_keeps_two_slots_on_the_same_day_apart(): void
    {
        $subEvents = array_merge(
            $this->weekly('2026-08-05', '10:00', '12:00', 4),
            $this->weekly('2026-08-05', '14:00', '18:00', 4)
        );

        $this->assertSame(
            [
                'wednesday' => [
                    ['gte' => 1000, 'lt' => 1200],
                    ['gte' => 1400, 'lt' => 1800],
                ],
            ],
            $this->resolver->resolve($subEvents, $this->timezone)
        );
    }

    /**
     * Slots that touch without overlapping form one uninterrupted run, so they are one range.
     *
     * @test
     */
    public function it_joins_two_slots_that_touch(): void
    {
        $subEvents = array_merge(
            $this->weekly('2026-08-05', '10:00', '12:00', 4),
            $this->weekly('2026-08-05', '12:00', '14:00', 4)
        );

        $this->assertSame(
            ['wednesday' => [['gte' => 1000, 'lt' => 1400]]],
            $this->resolver->resolve($subEvents, $this->timezone)
        );
    }

    /**
     * The hours the slots have in common reach the minimum even though neither variant does on its
     * own. Only that shared part is dependable, the rest is dropped.
     *
     * @test
     */
    public function it_keeps_only_the_hours_that_reach_the_minimum(): void
    {
        $subEvents = array_merge(
            $this->weekly('2026-08-05', '10:00', '12:00', 3),
            $this->weekly('2026-08-26', '10:00', '13:00', 3)
        );

        $this->assertSame(
            ['wednesday' => [['gte' => 1000, 'lt' => 1200]]],
            $this->resolver->resolve($subEvents, $this->timezone)
        );
    }

    /**
     * The worked example of the brainstorm: three Wednesdays of 11:00 to 12:00 and one of 10:00 to
     * 12:00. Only 11:00 to 12:00 reaches four occurrences, so an earlier search must not match. The
     * hour from 10:00 happens once and is not a dependable fixture.
     *
     * @test
     */
    public function it_drops_an_hour_that_only_one_occurrence_starts_earlier(): void
    {
        $subEvents = array_merge(
            $this->weekly('2026-08-05', '11:00', '12:00', 3),
            $this->weekly('2026-08-26', '10:00', '12:00', 1)
        );

        $this->assertSame(
            ['wednesday' => [['gte' => 1100, 'lt' => 1200]]],
            $this->resolver->resolve($subEvents, $this->timezone)
        );
    }

    /**
     * @test
     */
    public function it_resolves_nothing_when_the_slots_do_not_overlap(): void
    {
        $subEvents = array_merge(
            $this->weekly('2026-08-05', '10:00', '12:00', 3),
            $this->weekly('2026-08-26', '18:00', '20:00', 3)
        );

        $this->assertSame([], $this->resolver->resolve($subEvents, $this->timezone));
    }

    /**
     * Two sub-events overlapping on one date are one occurrence of the hours they share, not two.
     * Counting them twice would let two weeks reach a minimum of four.
     *
     * @test
     */
    public function it_counts_a_date_once_when_two_sub_events_overlap_on_it(): void
    {
        $subEvents = array_merge(
            $this->weekly('2026-08-05', '10:00', '12:00', 2),
            $this->weekly('2026-08-05', '11:00', '13:00', 2)
        );

        $this->assertSame([], $this->resolver->resolve($subEvents, $this->timezone));
    }

    /**
     * @test
     */
    public function it_rounds_the_hours_outward_to_the_quarter(): void
    {
        $subEvents = $this->weekly('2026-08-05', '10:05', '11:50', 4);

        $this->assertSame(
            ['wednesday' => [['gte' => 1000, 'lt' => 1200]]],
            $this->resolver->resolve($subEvents, $this->timezone)
        );
    }

    /**
     * @test
     */
    public function it_splits_a_sub_event_crossing_midnight_over_both_days_of_week(): void
    {
        $subEvents = $this->weekly('2026-08-01', '20:00', '26:00', 4);

        $this->assertSame(
            [
                'saturday' => [['gte' => 2000, 'lt' => 2400]],
                'sunday' => [['gte' => 0, 'lt' => 200]],
            ],
            $this->resolver->resolve($subEvents, $this->timezone)
        );
    }

    /**
     * A sub-event ending at midnight sharp is over when the next day starts, so it must not leave a
     * range of zero length behind on that day.
     *
     * @test
     */
    public function it_leaves_nothing_on_the_next_day_for_a_sub_event_ending_at_midnight(): void
    {
        $subEvents = $this->weekly('2026-08-01', '20:00', '24:00', 4);

        $this->assertSame(
            ['saturday' => [['gte' => 2000, 'lt' => 2400]]],
            $this->resolver->resolve($subEvents, $this->timezone)
        );
    }

    /**
     * @test
     */
    public function it_counts_every_day_a_multi_day_sub_event_spans(): void
    {
        $subEvents = $this->weekly('2026-08-01', '20:00', '70:00', 4);

        // Keyed in canonical week order, so Monday comes first even though the sub-event starts on
        // a Saturday.
        $this->assertSame(
            [
                'monday' => [['gte' => 0, 'lt' => 2200]],
                'saturday' => [['gte' => 2000, 'lt' => 2400]],
                'sunday' => [['gte' => 0, 'lt' => 2400]],
            ],
            $this->resolver->resolve($subEvents, $this->timezone)
        );
    }

    /**
     * The hours are read in the timezone of the offer, the same one localTimeRange uses, so a UTC
     * source date does not shift the slot an hour.
     *
     * @test
     */
    public function it_reads_the_hours_in_the_local_timezone(): void
    {
        $subEvents = [];
        foreach (['2026-08-05', '2026-08-12', '2026-08-19', '2026-08-26'] as $date) {
            $subEvents[] = [
                'startDate' => $date . 'T08:00:00+00:00',
                'endDate' => $date . 'T10:00:00+00:00',
            ];
        }

        $this->assertSame(
            ['wednesday' => [['gte' => 1000, 'lt' => 1200]]],
            $this->resolver->resolve($subEvents, $this->timezone)
        );
    }

    /**
     * @test
     */
    public function it_skips_sub_events_without_a_usable_date_range(): void
    {
        $subEvents = array_merge(
            $this->weekly('2026-08-05', '10:00', '12:00', 4),
            [
                ['endDate' => '2026-08-05T12:00:00+02:00'],
                ['startDate' => '2026-08-05T10:00:00+02:00'],
                ['startDate' => 'not a date', 'endDate' => '2026-08-05T12:00:00+02:00'],
                ['startDate' => '2026-08-05T12:00:00+02:00', 'endDate' => '2026-08-05T10:00:00+02:00'],
            ]
        );

        $this->assertSame(
            ['wednesday' => [['gte' => 1000, 'lt' => 1200]]],
            $this->resolver->resolve($subEvents, $this->timezone)
        );
    }

    /**
     * @param string $firstDate
     *   The date of the first occurrence, as Y-m-d.
     * @param string $opens
     *   The local start time as H:i.
     * @param string $closes
     *   The local end time as H:i. Hours beyond 24 run into the following days.
     * @return list<array{startDate: string, endDate: string}>
     */
    private function weekly(string $firstDate, string $opens, string $closes, int $occurrences): array
    {
        $start = new DateTimeImmutable($firstDate . 'T00:00:00', $this->timezone);

        [$opensHours, $opensMinutes] = array_map('intval', explode(':', $opens));
        [$closesHours, $closesMinutes] = array_map('intval', explode(':', $closes));

        $subEvents = [];
        for ($week = 0; $week < $occurrences; $week++) {
            $date = $start->modify('+' . ($week * 7) . ' days');

            $subEvents[] = [
                'startDate' => $date->modify("+{$opensHours} hours +{$opensMinutes} minutes")->format(DateTime::ATOM),
                'endDate' => $date->modify("+{$closesHours} hours +{$closesMinutes} minutes")->format(DateTime::ATOM),
            ];
        }

        return $subEvents;
    }
}
