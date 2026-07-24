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

        // Both from and to are always required together by the schema, so we
        // either map the full range or leave the draft untouched.
        if (!isset($range['from'], $range['to'])) {
            return $draft;
        }

        $draft['_birthdateRange'] = [
            'gte' => $range['from'],
            'lte' => $range['to'],
        ];

        return $draft;
    }
}
