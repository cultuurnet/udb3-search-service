<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components;

use ValueObjects\Enum\Enum;

/**
 * @method static FallbackType EVENT()
 * @method static FallbackType PLACE()
 * @method static FallbackType ORGANIZER()
 */
class FallbackType extends Enum
{
    const EVENT = 'Event';
    const PLACE = 'Place';
    const ORGANIZER = 'Organizer';
}
