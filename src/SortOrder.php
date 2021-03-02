<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use ValueObjects\Enum\Enum;

/**
 * @method static SortOrder ASC()
 * @method static SortOrder DESC()
 */
final class SortOrder extends Enum
{
    public const ASC = 'asc';
    public const DESC = 'desc';
}
