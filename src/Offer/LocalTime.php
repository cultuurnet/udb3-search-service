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
        // Not a plain 0 to 2359 range check, because 1099 and 1160 pass that without being on any
        // clock, and they reach Elasticsearch as a range bound over minutes that do not exist.
        if ($value < 0 || intdiv($value, 100) > 23 || $value % 100 > 59) {
            throw new UnsupportedParameterValue(
                'The time value ' . $value . ' is not a time of day between 0000 and 2359'
            );
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
