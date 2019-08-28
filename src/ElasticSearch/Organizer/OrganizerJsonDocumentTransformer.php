<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\Organizer;

use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use CultuurNet\UDB3\Search\ElasticSearch\IdUrlParserInterface;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\CopyJsonInterface;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Logging\CopyJsonPsrLogger;
use CultuurNet\UDB3\Search\JsonDocument\JsonDocumentTransformerInterface;
use Psr\Log\LoggerInterface;

class OrganizerJsonDocumentTransformer implements JsonDocumentTransformerInterface
{
    /**
     * @var CopyJsonInterface
     */
    private $jsonCopier;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        IdUrlParserInterface $idUrlParser,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;

        $this->jsonCopier = new CopyJsonOrganizer(
            new CopyJsonPsrLogger($this->logger),
            $idUrlParser
        );
    }

    /**
     * @param JsonDocument $jsonDocument
     * @return JsonDocument
     */
    public function transform(JsonDocument $jsonDocument)
    {
        $body = $jsonDocument->getBody();

        $newBody = new \stdClass();

        $this->jsonCopier->copy($body, $newBody);

        return $jsonDocument->withBody($newBody);
    }
}
