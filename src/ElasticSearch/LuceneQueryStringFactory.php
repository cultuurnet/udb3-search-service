<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch;

use CultuurNet\UDB3\Search\QueryStringFactory;

final class LuceneQueryStringFactory implements QueryStringFactory
{
    public function fromString(string $queryString): LuceneQueryString
    {
        return new LuceneQueryString($queryString);
    }
}
