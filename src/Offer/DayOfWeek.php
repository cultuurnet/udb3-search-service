<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

use CultuurNet\UDB3\Search\UnsupportedParameterValue;
use DateTimeInterface;

enum DayOfWeek: string
{
    case Monday = 'monday';
    case Tuesday = 'tuesday';
    case Wednesday = 'wednesday';
    case Thursday = 'thursday';
    case Friday = 'friday';
    case Saturday = 'saturday';
    case Sunday = 'sunday';

    public static function fromDate(DateTimeInterface $date): self
    {
        // format('l') is always the English weekday name, independent of locale, so this never fails.
        return self::from(strtolower($date->format('l')));
    }

    /**
     * Parses a user-supplied value case-insensitively, rejecting anything that is not a weekday.
     */
    public static function fromString(string $value): self
    {
        $weekday = self::tryFrom(strtolower($value));
        if ($weekday === null) {
            throw new UnsupportedParameterValue(
                'Unknown day of week value "' . $value . '". Should be one of ' . implode(', ', array_map(
                    static fn (self $day): string => $day->value,
                    self::cases()
                ))
            );
        }

        return $weekday;
    }
}
