<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\JsonDocument;

use CultuurNet\UDB3\Search\Json;
use CultuurNet\UDB3\Search\ReadModel\JsonDocument;

final class JsonDocumentTransformer
{
    private JsonTransformer $jsonTransformer;

    public function __construct(JsonTransformer $jsonTransformer)
    {
        $this->jsonTransformer = $jsonTransformer;
    }

    public function transform(JsonDocument $jsonDocument): JsonDocument
    {
        $from = Json::decodeAssociatively($jsonDocument->getRawBody());

        return (new JsonDocument($jsonDocument->getId()))
            ->withBody($this->jsonTransformer->transform($from));
    }
}
