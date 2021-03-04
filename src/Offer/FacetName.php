<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

use ValueObjects\Enum\Enum;

/**
 * @method static FacetName REGIONS()
 * @method static FacetName TYPES()
 * @method static FacetName THEMES()
 * @method static FacetName FACILITIES()
 * @method static FacetName LABELS()
 */
final class FacetName extends Enum
{
    public const REGIONS = 'regions';
    public const TYPES = 'types';
    public const THEMES = 'themes';
    public const FACILITIES = 'facilities';
    public const LABELS = 'labels';
}
