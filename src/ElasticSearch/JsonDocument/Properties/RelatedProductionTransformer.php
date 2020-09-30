<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;

final class RelatedProductionTransformer implements JsonTransformer
{
    public function transform(array $from, array $draft = []): array
    {
        if (!isset($from['production']['id'])) {
            return $draft;
        }

        $draft['production'] = [
          'id' => $from['production']['id'],
        ];

        return $draft;
    }
}
