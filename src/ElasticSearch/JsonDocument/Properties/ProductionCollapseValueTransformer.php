<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\ElasticSearch\IdUrlParserInterface;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;

final class ProductionCollapseValueTransformer implements JsonTransformer
{
    /**
     * @var IdUrlParserInterface
     */
    private $idUrlParser;

    public function __construct(IdUrlParserInterface $idUrlParser)
    {
        $this->idUrlParser = $idUrlParser;
    }

    public function transform(array $from, array $draft = []): array
    {
        // Offers from the same production should have the same production collapse value.
        if (isset($from['production']['id'])) {
            $draft['productionCollapseValue'] = "production-" . $from['production']['id'];
            return $draft;
        }

        // Offers that do not belong to a production should also have a production collapse value, but a unique one.
        // Otherwise the collapse will group them all together since they will all have `null` as a value.
        if (isset($from['@id'])) {
            $id = $this->idUrlParser->getIdFromUrl($from['@id']);
            $draft['productionCollapseValue'] = 'single-offer-' . $id;
            return $draft;
        }

        return $draft;
    }
}
