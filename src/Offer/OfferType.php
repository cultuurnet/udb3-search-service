<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

use ValueObjects\Enum\Enum;

/**
 * @method static OfferType EVENT()
 * @method static OfferType PLACE()
 */
class OfferType extends Enum
{
    const EVENT = 'Event';
    const PLACE = 'Place';

    public static function fromCaseInsensitiveValue($value)
    {
        return self::fromNative(ucfirst(strtolower($value)));
    }
}
