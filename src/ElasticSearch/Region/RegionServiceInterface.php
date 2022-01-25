<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Region;

use CultuurNet\UDB3\Search\Region\RegionId;

interface RegionServiceInterface
{
    /**
     * @return RegionId[]
     */
    public function getRegionIds(array $geoShape): array;
}
