<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\JsonDocument;

use CultuurNet\UDB3\Search\ReadModel\JsonDocument;

final class JsonDocumentTransformer
{
    /**
     * @var JsonTransformer
     */
    private $jsonTransformer;

    public function __construct(JsonTransformer $jsonTransformer)
    {
        $this->jsonTransformer = $jsonTransformer;
    }

    public function transform(JsonDocument $jsonDocument): JsonDocument
    {
        $from = json_decode($jsonDocument->getRawBody(), true);

        return (new JsonDocument($jsonDocument->getId()))
            ->withBody($this->jsonTransformer->transform($from));
    }
}
