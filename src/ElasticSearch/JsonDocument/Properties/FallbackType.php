<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use ValueObjects\Enum\Enum;

/**
 * @method static FallbackType EVENT()
 * @method static FallbackType PLACE()
 * @method static FallbackType ORGANIZER()
 */
final class FallbackType extends Enum
{
    public const EVENT = 'Event';
    public const PLACE = 'Place';
    public const ORGANIZER = 'Organizer';
}
