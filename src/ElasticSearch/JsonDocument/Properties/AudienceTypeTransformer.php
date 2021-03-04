<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;

final class AudienceTypeTransformer implements JsonTransformer
{
    public function transform(array $from, array $draft = []): array
    {
        $draft['audienceType'] = $from['audience']['audienceType'] ?? 'everyone';
        return $draft;
    }
}
