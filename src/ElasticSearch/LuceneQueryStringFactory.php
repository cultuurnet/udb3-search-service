<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch;

use CultuurNet\UDB3\Search\QueryStringFactory;

final class LuceneQueryStringFactory implements QueryStringFactory
{
    /**
     * @return LuceneQueryString
     */
    public function fromString(string $queryString)
    {
        return new LuceneQueryString($queryString);
    }
}
