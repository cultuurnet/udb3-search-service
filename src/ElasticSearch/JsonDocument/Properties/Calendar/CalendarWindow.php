<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\Calendar;

use Cake\Chronos\Chronos;
use DateTimeInterface;

/**
 * The stretches of calendar the indexer walks, together so they can be read against each other. Every
 * walk steps a day at a time, so a faulty end date like the year 5020 exhausts memory without one.
 */
final class CalendarWindow
{
    /**
     * Backstop for a start date in the far past, which {@see indexed()} leaves alone. About thirty years,
     * so it only fires on broken data.
     */
    public const MAX_DAYS = 11000;

    private const RECURRING_BEHIND = '-6 months';

    private const RECURRING_AHEAD = '+1 year';

    private const INDEXED_AHEAD = '+5 years';

    private const PERMANENT_BEHIND = '-6 months';

    private const PERMANENT_AHEAD = '+12 months';

    private function __construct(
        private readonly Chronos $start,
        private readonly Chronos $end
    ) {
    }

    /**
     * What the weekly pattern is read from. A pattern repeats, so looking further adds nothing but a
     * schedule the offer has since changed. Shared by recurringOnDayOfWeek and recurringOnLocalTimeRange
     * so the two cannot disagree.
     */
    public static function recurring(): self
    {
        $now = Chronos::now();

        return new self(
            $now->modify(self::RECURRING_BEHIND)->startOfDay(),
            $now->modify(self::RECURRING_AHEAD)
        );
    }

    /**
     * What sub-events are built over, so it decides how far ahead a date search finds the offer. Counted
     * from the start date when the calendar has not begun yet, otherwise from today, so a long running
     * one keeps reaching ahead and a future one is not dropped for starting out of reach.
     */
    public static function indexed(Chronos $startDate, Chronos $endDate): self
    {
        $now = Chronos::now();
        $cap = ($startDate > $now ? $startDate : $now)->modify(self::INDEXED_AHEAD);

        return new self($startDate, $endDate < $cap ? $endDate : $cap);
    }

    /**
     * What a permanent calendar is read over. It carries no dates of its own, so this rolls with today.
     */
    public static function permanent(): self
    {
        $now = Chronos::now();

        return new self($now->modify(self::PERMANENT_BEHIND), $now->modify(self::PERMANENT_AHEAD));
    }

    public function start(): Chronos
    {
        return $this->start;
    }

    public function end(): Chronos
    {
        return $this->end;
    }

    public function covers(DateTimeInterface $date): bool
    {
        return $date >= $this->start && $date <= $this->end;
    }
}
