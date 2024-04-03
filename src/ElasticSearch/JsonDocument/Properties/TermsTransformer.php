<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;

final class TermsTransformer implements JsonTransformer
{
    private bool $includeTermsForFreeText;

    private bool $includeTermsForAggregations;

    public function __construct(bool $includeTermsForFreeText, bool $includeTermsForAggregations)
    {
        $this->includeTermsForFreeText = $includeTermsForFreeText;
        $this->includeTermsForAggregations = $includeTermsForAggregations;
    }

    public function transform(array $from, array $draft = []): array
    {
        $terms = $this->getTerms($from);

        if (empty($terms)) {
            return $draft;
        }

        $draft['terms'] = $terms;

        if ($this->includeTermsForFreeText) {
            $draft['terms_free_text'] = $terms;
        }

        if ($this->includeTermsForAggregations) {
            $draft = $this->transformTermsForAggregations($from, $draft);
        }

        return $draft;
    }

    private function getTerms(array $from): array
    {
        if (!isset($from['terms'])) {
            return [];
        }

        return array_map(
            fn (array $term): array =>
                // Don't copy all properties, just those we're interested in.;
                [
                'id' => $term['id'],
                'label' => $term['label'],
            ],
            $from['terms']
        );
    }

    private function transformTermsForAggregations(array $from, array $draft): array
    {
        $typeIds = $this->getTermIdsByDomain($from, 'eventtype');
        $themeIds = $this->getTermIdsByDomain($from, 'theme');
        $facilityIds = $this->getTermIdsByDomain($from, 'facility');

        if (!empty($typeIds)) {
            $draft['typeIds'] = $typeIds;
        }

        if (!empty($themeIds)) {
            $draft['themeIds'] = $themeIds;
        }

        if (!empty($facilityIds)) {
            $draft['facilityIds'] = $facilityIds;
        }

        return $draft;
    }

    private function getTermIdsByDomain(array $from, string $domain): array
    {
        // Don't use $this->getTerms() here as the resulting terms do not
        // contain the "domain" property.
        $terms = $from['terms'] ?? [];

        $filteredByDomain = array_filter(
            $terms,
            fn (array $term): bool => isset($term['domain'], $term['id']) && $term['domain'] === $domain
        );

        $mappedToIds = array_map(
            fn (array $term) => $term['id'],
            $filteredByDomain
        );

        // Remove duplicates using array_unique and then convert to a list with consecutive keys (0, 1, 2...) using
        // array_values() to avoid gaps and as a result the array becoming an object in JSON.
        return array_values(
            array_unique(
                $mappedToIds
            )
        );
    }
}
