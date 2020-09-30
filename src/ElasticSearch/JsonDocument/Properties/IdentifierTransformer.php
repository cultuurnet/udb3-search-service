<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\ElasticSearch\IdUrlParserInterface;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerLogger;

final class IdentifierTransformer implements JsonTransformer
{
    /**
     * @var JsonTransformerLogger
     */
    private $logger;

    /**
     * @var IdUrlParserInterface
     */
    private $idUrlParser;

    /**
     * @var FallbackType
     */
    private $fallbackType;

    /**
     * @var bool
     */
    private $setMainId;

    public function __construct(
        JsonTransformerLogger $logger,
        IdUrlParserInterface $idUrlParser,
        FallbackType $fallbackType,
        bool $setMainId = false
    ) {
        $this->logger = $logger;
        $this->idUrlParser = $idUrlParser;
        $this->fallbackType = $fallbackType;
        $this->setMainId = $setMainId;
    }

    public function transform(array $from, array $draft = []): array
    {
        if (isset($from["@id"])) {
            $draft["@id"] = $from["@id"];
        } else {
            $this->logger->logMissingExpectedField("@id");
        }

        $draft["@type"] = $from["@type"] ?? $this->fallbackType->toNative();

        // Not included in the if statement above because it should be under
        // @type in the JSON. No else statement because we don't want to log a
        // missing @id twice.
        if (isset($from["@id"])) {
            $draft['id'] = $this->idUrlParser->getIdFromUrl($from["@id"]);

            if ($this->setMainId) {
                $draft['mainId'] = $this->idUrlParser->getIdFromUrl($from["@id"]);
            }
        }

        return $draft;
    }
}
