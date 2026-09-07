<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\Calendar;

use Cake\Chronos\Chronos;
use DateTimeInterface;

/**
 * The stretches of calendar the indexer walks, together so they can be read against each other.
 *
 * Every walk steps a day at a time, so every walk needs an end. Without one a faulty end date, like the
 * year 5020 an editor can type, walks a million days and exhausts memory.
 */
final class CalendarWindow
{
    /**
     * Backstop for a start date in the far past, which {@see indexed()} leaves alone because the days
     * behind us are the ones already searched on. About thirty years, so it only fires on broken data.
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
     * What the weekly pattern is read from. A pattern repeats, so a year of it says everything the
     * remaining years would, and looking no further either way keeps a schedule the offer has since
     * changed out of the answer. Shared by recurringOnDayOfWeek and recurringOnLocalTimeRange so the two
     * never disagree on which occurrences count. It lands on the same stretch as {@see permanent()},
     * which answers the same question about how far back is still current.
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
     * What sub-events are built over, which decides how far ahead a date search still finds the offer.
     * Counted from today, so a calendar running since years back keeps reaching five years ahead, or from
     * the start date when it has not begun yet, so one starting in 2035 is not dropped for beginning out
     * of reach.
     */
    public static function indexed(Chronos $startDate, Chronos $endDate): self
    {
        $now = Chronos::now();
        $cap = ($startDate > $now ? $startDate : $now)->modify(self::INDEXED_AHEAD);

        return new self($startDate, $endDate < $cap ? $endDate : $cap);
    }

    /**
     * What a permanent calendar is read over. It carries no dates of its own, so the window rolls along
     * with today.
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
