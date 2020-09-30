<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument;

use CultuurNet\UDB3\Search\ElasticSearch\IdUrlParserInterface;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\CopyJsonInterface;
use CultuurNet\UDB3\Search\JsonDocument\JsonDocumentTransformerInterface;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerPsrLogger;
use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use Psr\Log\LoggerInterface;

class OrganizerJsonDocumentTransformer implements JsonDocumentTransformerInterface
{
    /**
     * @var OrganizerTransformer
     */
    private $organizerTransformer;

    public function __construct(
        IdUrlParserInterface $idUrlParser,
        LoggerInterface $logger
    ) {
        $this->organizerTransformer = new OrganizerTransformer(
            new JsonTransformerPsrLogger($logger),
            $idUrlParser
        );
    }

    public function transform(JsonDocument $jsonDocument): JsonDocument
    {
        $body = $jsonDocument->getBody();

        $newBody = new \stdClass();

        $this->organizerTransformer->copy($body, $newBody);

        return $jsonDocument->withBody($newBody);
    }
}
