<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

use CultuurNet\UDB3\Search\UnsupportedParameterValue;
use CultuurNet\UDB3\Search\Natural;

/**
 * The localTimeFrom and localTimeTo search parameters, as an HHMM integer between 0 and 2359.
 */
final class LocalTime extends Natural
{
    public function __construct(int $value)
    {
        if ($value < 0 || $value > 2359) {
            throw new UnsupportedParameterValue('The time value ' . $value . ' is not between 0 and 2359');
        }

        parent::__construct($value);
    }
}
