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
    private const RECURRING_BEHIND = '-6 months';

    private const RECURRING_AHEAD = '+1 year';

    private function __construct(
        private readonly Chronos $start,
        private readonly Chronos $end
    ) {
    }

    /**
     * What the weekly pattern is read from. A pattern repeats, so a year of it says everything the
     * remaining years would, and looking no further either way keeps a schedule the offer has since
     * changed out of the answer. Shared by recurringOnDayOfWeek and recurringOnLocalTimeRange so the two
     * never disagree on which occurrences count.
     */
    public static function recurring(): self
    {
        $now = Chronos::now();

        return new self(
            $now->modify(self::RECURRING_BEHIND)->startOfDay(),
            $now->modify(self::RECURRING_AHEAD)
        );
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
