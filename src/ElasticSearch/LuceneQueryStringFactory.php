<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch;

use CultuurNet\UDB3\Search\QueryStringFactory;

final class LuceneQueryStringFactory implements QueryStringFactory
{
    use ElasticSearch5Compatibility;

    public function fromString(string $queryString): LuceneQueryString
    {
        // Rewrite `field:(a..b OR c..d)` first and distribute the field name onto
        // every range. Lucene's query_string parser does NOT propagate the field
        // prefix to bracketed range expressions inside a group, so leaving them
        // as `field:([a TO b] OR [c TO d])` would only attach the field to the
        // first range — the rest fall back to the default field.
        $queryString = preg_replace_callback(
            '/([\w.]+):\(([^()]*\d{4}-\d{2}-\d{2}\.\.\d{4}-\d{2}-\d{2}[^()]*)\)/',
            static function (array $matches): string {
                $field = $matches[1];
                $inner = preg_replace(
                    '/(\d{4}-\d{2}-\d{2})\.\.(\d{4}-\d{2}-\d{2})/',
                    "{$field}:[$1 TO $2]",
                    $matches[2]
                ) ?? $matches[2];
                return "({$inner})";
            },
            $queryString
        ) ?? $queryString;

        // Any remaining shorthand date ranges `YYYY-MM-DD..YYYY-MM-DD` (outside
        // of `field:(...)` groups) are rewritten to standard Lucene range syntax.
        $queryString = preg_replace(
            '/(\d{4}-\d{2}-\d{2})\.\.(\d{4}-\d{2}-\d{2})/',
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
