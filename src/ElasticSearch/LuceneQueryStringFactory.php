<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch;

use CultuurNet\UDB3\Search\QueryStringFactoryInterface;

class LuceneQueryStringFactory implements QueryStringFactoryInterface
{
    /**
     * @param string $queryString
     * @return LuceneQueryString
     */
    public function fromString($queryString)
    {
        return new LuceneQueryString($queryString);
    }
}
