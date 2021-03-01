<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\JsonDocument;

interface JsonTransformer
{
    /**
     * @param array $from
     *   Json to copy data/properties from
     * @param array $draft
     *   Json to copy data/properties to (immutable)
     * @return array
     *   Resulting json
     */
    public function transform(array $from, array $draft = []): array;
}
