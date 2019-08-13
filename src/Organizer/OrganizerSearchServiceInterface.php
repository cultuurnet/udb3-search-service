<?php

namespace CultuurNet\UDB3\Search\Organizer;

use CultuurNet\UDB3\Search\PagedResultSet;

interface OrganizerSearchServiceInterface
{
    /**
     * @param OrganizerQueryBuilderInterface $queryBuilder
     * @return PagedResultSet
     */
    public function search(OrganizerQueryBuilderInterface $queryBuilder);
}
