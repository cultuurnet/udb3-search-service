<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch;

use CultuurNet\UDB3\Search\QueryStringFactory;

final class LuceneQueryStringFactory implements QueryStringFactory
{
    use ElasticSearch5Compatibility;

    public function fromString(string $queryString): LuceneQueryString
    {
        // ES8 removed the _type metadata field. Queries using _type: filters must
        // use @type: instead. Rewrite here so callers don't need to be ES-version-aware.
        if (!$this->usesDocumentTypes()) {
            $queryString = preg_replace('/_type:(\S+)/', '@type:$1', $queryString) ?? $queryString;
        }

        return new LuceneQueryString($queryString);
    }
}
