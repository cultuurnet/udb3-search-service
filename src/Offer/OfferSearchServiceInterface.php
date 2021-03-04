<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

use CultuurNet\UDB3\Search\PagedResultSet;

interface OfferSearchServiceInterface
{
    /**
     * @return PagedResultSet
     */
    public function search(OfferQueryBuilderInterface $queryBuilder);
}
