<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Offer;

use ArrayIterator;
use Countable;
use CultuurNet\UDB3\Search\Offer\DayOfWeek;
use IteratorAggregate;

/**
 * @implements IteratorAggregate<int, OpeningHour>
 */
final readonly class OpeningHours implements IteratorAggregate, Countable
{
    /**
     * @var OpeningHour[]
     */
    private array $openingHours;

    /**
     * @param OpeningHour[] $openingHours
     */
    public function __construct(array $openingHours = [])
    {
        $this->openingHours = array_values($openingHours);
    }

    /**
     * Returns the same opening hours, but with a separate opening hour per day of the week.
     */
    public function splitPerDay(): self
    {
        $individualOpeningHours = [];
        foreach ($this->openingHours as $openingHour) {
            foreach ($openingHour->getDaysOfWeek() as $dayOfWeek) {
                $individualOpeningHours[] = new OpeningHour(
                    [$dayOfWeek],
                    $openingHour->getOpens(),
                    $openingHour->getCloses(),
                    $openingHour->getChildcare()
                );
            }
        }

        return new self($individualOpeningHours);
    }

    public function sortedByDayAndOpeningTime(): self
    {
        $sorted = $this->openingHours;

        usort($sorted, static function (OpeningHour $a, OpeningHour $b): int {
            $weekdayA = DayOfWeek::from($a->getDaysOfWeek()[0])->position();
            $weekdayB = DayOfWeek::from($b->getDaysOfWeek()[0])->position();
            $fullHoursA = $weekdayA * 24 + (int) $a->getOpens();
            $fullHoursB = $weekdayB * 24 + (int) $b->getOpens();

            return $fullHoursA <=> $fullHoursB;
        });

        return new self($sorted);
    }

    /**
     * Regroups the opening hours so that every day of the week is listed at most once: the
     * timespans of a day end up in the same group, and days with exactly the same timespans
     * share one. Every opening hour of a group carries the days of that group, so a caller
     * can read them off any of them.
     *
     * @return self[] in the order of the week
     */
    public function groupedByIdenticalTimespans(): array
    {
        $timespansPerDay = [];
        foreach ($this->splitPerDay()->sortedByDayAndOpeningTime() as $openingHour) {
            $timespansPerDay[$openingHour->getDaysOfWeek()[0]][] = $openingHour;
        }

        $daysPerTimespans = [];
        foreach ($timespansPerDay as $dayOfWeek => $timespans) {
            $daysPerTimespans[self::signatureOf($timespans)][] = (string) $dayOfWeek;
        }

        $groups = [];
        foreach ($daysPerTimespans as $daysOfWeek) {
            $groups[] = new self(
                array_map(
                    static fn (OpeningHour $timespan): OpeningHour => new OpeningHour(
                        $daysOfWeek,
                        $timespan->getOpens(),
                        $timespan->getCloses(),
                        $timespan->getChildcare()
                    ),
                    $timespansPerDay[$daysOfWeek[0]]
                )
            );
        }

        return $groups;
    }

    /**
     * Identifies the timespans of a day, so two days that open and close at the same hours
     * and have the same childcare can be listed together.
     *
     * @param OpeningHour[] $timespans
     */
    private static function signatureOf(array $timespans): string
    {
        return implode(
            '|',
            array_map(
                static function (OpeningHour $timespan): string {
                    $childcare = $timespan->getChildcare();

                    return $timespan->getOpens() . '-' . $timespan->getCloses() . '-'
                        . ($childcare === null ? '' : $childcare->getStart() . '-' . $childcare->getEnd());
                },
                $timespans
            )
        );
    }

    /**
     * Returns the opening hours that apply on the given day of the week.
     */
    public function onDayOfWeek(string $dayOfWeek): self
    {
        return new self(
            array_filter(
                $this->openingHours,
                static fn (OpeningHour $openingHour): bool => in_array($dayOfWeek, $openingHour->getDaysOfWeek(), true)
            )
        );
    }

    /**
     * Returns the days of the week without any opening hour, in the order of the week.
     * Without opening hours at all there are none, as that means open every day.
     *
     * @return string[]
     */
    public function closedDaysOfWeek(): array
    {
        if ($this->isEmpty()) {
            return [];
        }

        $closedDaysOfWeek = [];
        foreach (DayOfWeek::cases() as $dayOfWeek) {
            if ($this->onDayOfWeek($dayOfWeek->value)->isEmpty()) {
                $closedDaysOfWeek[] = $dayOfWeek->value;
            }
        }

        return $closedDaysOfWeek;
    }

    /**
     * Returns the childcare that every opening hour has in common, or null when they
     * differ or when at least one opening hour has no childcare at all.
     */
    public function sharedChildcare(): ?Childcare
    {
        $shared = null;

        foreach ($this->openingHours as $openingHour) {
            $childcare = $openingHour->getChildcare();

            if ($childcare === null) {
                return null;
            }

            if ($shared === null) {
                $shared = $childcare;
                continue;
            }

            if (!$shared->equals($childcare)) {
                return null;
            }
        }

        return $shared;
    }

    /**
     * Retrieve the earliest time for the specified daysOfWeek.
     *
     * @param string[] $daysOfWeek
     */
    public function earliestTimeOn(array $daysOfWeek): string
    {
        $earliest = '';
        foreach ($this->withExactlyTheseDaysOfWeek($daysOfWeek) as $openingHour) {
            if ($earliest === '' || $openingHour->getOpens() < $earliest) {
                $earliest = $openingHour->getOpens();
            }
        }

        return $earliest;
    }

    /**
     * Retrieve the latest time for the specified daysOfWeek.
     *
     * @param string[] $daysOfWeek
     */
    public function latestTimeOn(array $daysOfWeek): string
    {
        $latest = '';
        foreach ($this->withExactlyTheseDaysOfWeek($daysOfWeek) as $openingHour) {
            if ($latest === '' || $openingHour->getCloses() > $latest) {
                $latest = $openingHour->getCloses();
            }
        }

        return $latest;
    }

    public function isEmpty(): bool
    {
        return $this->openingHours === [];
    }

    /**
     * @return OpeningHour[]
     */
    public function toArray(): array
    {
        return $this->openingHours;
    }

    public function count(): int
    {
        return count($this->openingHours);
    }

    /**
     * @return ArrayIterator<int, OpeningHour>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->openingHours);
    }

    /**
     * @param string[] $daysOfWeek
     * @return OpeningHour[]
     */
    private function withExactlyTheseDaysOfWeek(array $daysOfWeek): array
    {
        return array_filter(
            $this->openingHours,
            static fn (OpeningHour $openingHour): bool => $daysOfWeek === $openingHour->getDaysOfWeek()
        );
    }
}
