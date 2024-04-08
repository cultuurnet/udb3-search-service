<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Organizer;

use CultuurNet\UDB3\Search\PagedResultSet;

interface OrganizerSearchServiceInterface
{
    public function search(OrganizerQueryBuilderInterface $queryBuilder): PagedResultSet;
}
