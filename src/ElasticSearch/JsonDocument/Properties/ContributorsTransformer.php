<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;

final class ContributorsTransformer implements JsonTransformer
{
    public function transform(array $from, array $draft = []): array
    {
        if (isset($from['contributors'])) {
            $draft['contributors'] = $from['contributors'];
        }

        return $draft;
    }
}
