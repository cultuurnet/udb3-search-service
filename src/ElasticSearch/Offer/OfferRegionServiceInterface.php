<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\Offer;

use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use CultuurNet\UDB3\Search\Region\RegionId;

interface OfferRegionServiceInterface
{
    /**
     * @param OfferType $offerType
     * @param JsonDocument $jsonDocument
     * @return RegionId[]
     */
    public function getRegionIds(OfferType $offerType, JsonDocument $jsonDocument);
}
