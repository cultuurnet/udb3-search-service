<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;

final class TermsTransformer implements JsonTransformer
{
    public function transform(array $from, array $draft = []): array
    {
        $terms = $this->getTerms($from);
        if (!empty($terms)) {
            $draft['terms'] = $terms;
        }
        return $draft;
    }

    private function getTerms(array $from): array
    {
        if (!isset($from['terms'])) {
            return [];
        }

        return array_map(
            function (array $term) {
                // Don't copy all properties, just those we're interested in.;
                return [
                    'id' => $term['id'],
                    'label' => $term['label'],
                ];
            },
            $from['terms']
        );
    }
}
