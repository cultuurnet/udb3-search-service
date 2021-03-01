<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch;

use CultuurNet\UDB3\Search\AbstractDistance;

class ElasticSearchDistance extends AbstractDistance
{
    public const DISTANCE_REGEX = '/^\s*(\d+\.?\d*)\s*([a-zA-Z]+)\s*$/';

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/5.0/common-options.html#distance-units
     */
    public const VALID_DISTANCE_UNITS = [
        'mi',
        'miles',
        'yd',
        'yards',
        'ft',
        'feet',
        'in',
        'inch',
        'km',
        'kilometers',
        'm',
        'meters',
        'cm',
        'centimeters',
        'mm',
        'millimeters',
        'NM',
        'nmi',
        'nauticalmiles',
    ];

    /**
     * @param string $value
     */
    public function __construct($value)
    {
        $matches = [];
        $regexResult = preg_match(self::DISTANCE_REGEX, $value, $matches);

        if (!$regexResult || count($matches) !== 3) {
            throw new \InvalidArgumentException('Distance is not in a valid format.');
        }

        $distance = $matches[1];
        $unit = $matches[2];

        if (!in_array($unit, self::VALID_DISTANCE_UNITS)) {
            throw new \InvalidArgumentException('Distance uses an unsupported unit.');
        }

        // Concatenate the distance and unit without spaces to make sure we get a valid ElasticSearch distance string.
        parent::__construct($distance . $unit);
    }
}
