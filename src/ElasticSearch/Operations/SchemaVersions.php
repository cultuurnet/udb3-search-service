<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

final class SchemaVersions
{
    public const UDB3_CORE = 20251016121404;
    public const GEOSHAPES = 20250101000000;

    public const UDB3_CORE_MAPPING_HASH = 'dab2ed253d1dd6c9a7c6bc5d8d985d16';
    public const EVENT_MAPPING_HASH = '2093228dbdfb267de73983f6d0ac312a';
    public const PLACE_MAPPING_HASH = 'f44c880a0a0071aeb7ba9c4b42b3ca14';
    public const ORGANIZER_MAPPING_HASH = '7eb0580fef40c4496971620d76bf0495';
    public const REGION_MAPPING_HASH = '288cee8b6424972dfb0fbbe76766d886';
}
