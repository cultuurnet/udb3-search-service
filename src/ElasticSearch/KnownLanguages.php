<?php

namespace CultuurNet\UDB3\Search\ElasticSearch;

class KnownLanguages
{
    /**
     * @param string $fieldPattern
     * @return array
     */
    public function fieldNames(string $fieldPattern): array
    {
        // @todo: The list of known languages gets bigger.
        // @see https://jira.uitdatabank.be/browse/III-2161 (es and it)
        $knownLanguages = ['nl', 'fr', 'de', 'en'];

        return array_map(
            function ($languageCode) use ($fieldPattern) {
                return str_replace('{{lang}}', $languageCode, $fieldPattern);
            },
            $knownLanguages
        );
    }
}
