<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

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
}
