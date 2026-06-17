<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch;

use CultuurNet\UDB3\Search\QueryStringFactory;

final class LuceneQueryStringFactory implements QueryStringFactory
{
    use ElasticSearch5Compatibility;

    public function fromString(string $queryString): LuceneQueryString
    {
        // Rewrite shorthand date ranges `YYYY-MM-DD TO YYYY-MM-DD` to the
        // standard Lucene range syntax `[YYYY-MM-DD TO YYYY-MM-DD]`, so they
        // can be used inside `q` queries (incl. within OR-groups) without the
        // caller having to add the brackets. Already-bracketed ranges are left
        // untouched so we never double-wrap them.
        $queryString = preg_replace(
            '/(?<!\[)(\d{4}-\d{2}-\d{2})\s+TO\s+(\d{4}-\d{2}-\d{2})(?!\])/',
            '[$1 TO $2]',
            $queryString
        ) ?? $queryString;

        // ES8 removed the _type metadata field. Queries using _type: filters must
        // use @type: instead. Rewrite here so callers don't need to be ES-version-aware.
        if (!$this->usesDocumentTypes()) {
            $queryString = preg_replace('/_type:(\S+)/', '@type:$1', $queryString) ?? $queryString;
        }

        return new LuceneQueryString($queryString);
    }
}
