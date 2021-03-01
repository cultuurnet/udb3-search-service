<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

use ValueObjects\Enum\Enum;

/**
 * @method static Status AVAILABLE()
 * @method static Status UNAVAILABLE()
 * @method static Status TEMPORARILY_UNAVAILABLE()
 */
class Status extends Enum
{
    public const AVAILABLE = 'Available';
    public const UNAVAILABLE = 'Unavailable';
    public const TEMPORARILY_UNAVAILABLE = 'TemporarilyUnavailable';
}
