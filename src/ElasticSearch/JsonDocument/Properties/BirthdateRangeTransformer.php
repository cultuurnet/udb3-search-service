<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;

final class BirthdateRangeTransformer implements JsonTransformer
{
    public function transform(array $from, array $draft = []): array
    {
        if (!isset($from['birthdateRange']) || !is_array($from['birthdateRange'])) {
            return $draft;
        }

        $range = $from['birthdateRange'];

        if (isset($range['from'])) {
            $draft['birthdateRange']['gte'] = $range['from'];
        }

        if (isset($range['to'])) {
            $draft['birthdateRange']['lte'] = $range['to'];
        }

        return $draft;
    }
}
