<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Geocoding\Coordinate;

use CultuurNet\UDB3\Search\UnsupportedParameterValue;

final class Longitude extends Coordinate
{
    public function __construct($value)
    {
        parent::__construct($value);

        if ($value < -180 || $value > 180) {
            throw new UnsupportedParameterValue('Longitude should be between --180 and 180.');
        }
    }
}
