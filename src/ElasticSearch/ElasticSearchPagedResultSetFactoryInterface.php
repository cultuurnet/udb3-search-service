<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch;

use CultuurNet\UDB3\Search\PagedResultSet;

interface ElasticSearchPagedResultSetFactoryInterface
{
    /**
     * @param int $perPage
     *   Number of results that were requested per page.
     *   Not necessarily the actual number of results on the page, as
     *   more results could be requested than were actually returned.
     *   (In the case of the last page.)
     *
     * @param array $response
     *   Decoded JSON response from ElasticSearch.
     *
     */
    public function createPagedResultSet(int $perPage, array $response): PagedResultSet;
}
