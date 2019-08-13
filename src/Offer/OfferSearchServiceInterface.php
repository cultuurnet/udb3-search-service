<?php

namespace CultuurNet\UDB3\Search\Offer;

use CultuurNet\UDB3\Search\PagedResultSet;

interface OfferSearchServiceInterface
{
    /**
     * @param OfferQueryBuilderInterface $queryBuilder
     * @return PagedResultSet
     */
    public function search(OfferQueryBuilderInterface $queryBuilder);
}
