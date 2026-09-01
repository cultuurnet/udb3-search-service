<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

use CultuurNet\UDB3\Search\Natural;
use CultuurNet\UDB3\Search\UnsupportedParameterValue;
use DateTimeImmutable;

/**
 * A time on the local clock, without a date. Arrives as an HHMM integer from the search parameters
 * and as "H:MM" or "HH:MM" from the source JSON.
 */
final class LocalTime extends Natural
{
    private const PATTERN = '/^([01]?[0-9]|2[0-3]):([0-5][0-9])$/';

    public function __construct(int $value)
    {
        if ($value < 0 || $value > 2359) {
            throw new UnsupportedParameterValue('The time value ' . $value . ' is not between 0 and 2359');
        }

        parent::__construct($value);
    }

    /**
     * @param string $localTime
     *   A local time as "H:MM" or "HH:MM", the format Entry API stores childcare and opening hours in.
     */
    public static function tryFromString(string $localTime): ?self
    {
        if (!preg_match(self::PATTERN, $localTime, $matches)) {
            return null;
        }

        return new self((int) $matches[1] * 100 + (int) $matches[2]);
    }

    public function on(DateTimeImmutable $date): DateTimeImmutable
    {
        return $date->setTime(intdiv($this->toNative(), 100), $this->toNative() % 100);
    }
}
