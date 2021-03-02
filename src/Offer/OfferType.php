<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

use ValueObjects\Enum\Enum;

/**
 * @method static OfferType EVENT()
 * @method static OfferType PLACE()
 */
final class OfferType extends Enum
{
    public const EVENT = 'Event';
    public const PLACE = 'Place';

    public static function fromCaseInsensitiveValue($value)
    {
        return self::fromNative(ucfirst(strtolower($value)));
    }
}
