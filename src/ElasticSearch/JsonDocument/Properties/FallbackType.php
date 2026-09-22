<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

enum FallbackType: string
{
    case Event = 'Event';
    case Place = 'Place';
    case Organizer = 'Organizer';
}
