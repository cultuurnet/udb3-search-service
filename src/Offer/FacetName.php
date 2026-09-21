<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

use CultuurNet\UDB3\Search\UnsupportedParameterValue;

enum FacetName: string
{
    case Regions = 'regions';
    case Types = 'types';
    case Themes = 'themes';
    case Facilities = 'facilities';
    case Labels = 'labels';

    public static function fromString(string $value): self
    {
        $facetName = self::tryFrom(strtolower($value));
        if ($facetName === null) {
            throw new UnsupportedParameterValue("Unknown facet name '{$value}'.");
        }

        return $facetName;
    }
}
