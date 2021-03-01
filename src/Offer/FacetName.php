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
class FacetName extends Enum
{
    const REGIONS = 'regions';
    const TYPES = 'types';
    const THEMES = 'themes';
    const FACILITIES = 'facilities';
    const LABELS = 'labels';
}
