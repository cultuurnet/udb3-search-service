<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

use DateTimeImmutable;

final class TimeOfDay
{
    private const PATTERN = '/^([01]?[0-9]|2[0-3]):([0-5][0-9])$/';

    private function __construct(
        private readonly int $hours,
        private readonly int $minutes
    ) {
    }

    /**
     * @param string $timeOfDay
     *   A local time as "H:MM" or "HH:MM", the format Entry API stores childcare and opening hours in.
     */
    public static function tryFromString(string $timeOfDay): ?self
    {
        if (!preg_match(self::PATTERN, $timeOfDay, $matches)) {
            return null;
        }

        return new self((int) $matches[1], (int) $matches[2]);
    }

    public function on(DateTimeImmutable $date): DateTimeImmutable
    {
        return $date->setTime($this->hours, $this->minutes);
    }
}
