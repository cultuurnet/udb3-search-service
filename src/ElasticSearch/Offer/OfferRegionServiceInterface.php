<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Offer;

use CultuurNet\UDB3\Search\Region\RegionId;

interface OfferRegionServiceInterface
{
    /**
     * @return RegionId[]
     */
    public function getRegionIds(array $geoShape): array;
}
