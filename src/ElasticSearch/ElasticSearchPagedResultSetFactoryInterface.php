<?php

namespace CultuurNet\UDB3\Search\ElasticSearch;

use CultuurNet\UDB3\Search\PagedResultSet;
use ValueObjects\Number\Natural;

interface ElasticSearchPagedResultSetFactoryInterface
{
    /**
     * @param Natural $perPage
     *   Number of results that were requested per page.
     *   Not necessarily the actual number of results on the page, as
     *   more results could be requested than were actually returned.
     *   (In the case of the last page.)
     *
     * @param array $response
     *   Decoded JSON response from ElasticSearch.
     *
     * @return PagedResultSet
     */
    public function createPagedResultSet(Natural $perPage, array $response);
}
