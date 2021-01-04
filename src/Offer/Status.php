<?php

namespace CultuurNet\UDB3\Search\Offer;

use ValueObjects\Enum\Enum;

/**
 * @method Status AVAILABLE()
 * @method Status UNAVAILABLE()
 * @method Status TEMPORARILY_UNAVAILABLE()
 */
class Status extends Enum
{
    public const AVAILABLE = 'Available';
    public const UNAVAILABLE = 'Unavailable';
    public const TEMPORARILY_UNAVAILABLE = 'TemporarilyUnavailable';
}
