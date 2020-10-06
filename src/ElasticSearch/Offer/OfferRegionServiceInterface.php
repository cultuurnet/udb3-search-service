<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\Offer;

use CultuurNet\UDB3\Search\Region\RegionId;

interface OfferRegionServiceInterface
{
    /**
     * @param array $geoShape
     * @return RegionId[]
     */
    public function getRegionIds(array $geoShape): array;
}
