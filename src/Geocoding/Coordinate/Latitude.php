<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Geocoding\Coordinate;

use CultuurNet\UDB3\Search\UnsupportedParameterValue;

final class Latitude extends Coordinate
{
    public function __construct(float $value)
    {
        parent::__construct($value);

        if ($value < -90 || $value > 90) {
            throw new UnsupportedParameterValue('Latitude should be between -90 and 90.');
        }
    }
}
