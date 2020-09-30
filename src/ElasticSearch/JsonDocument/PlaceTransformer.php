<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument;

use CultuurNet\UDB3\Search\ElasticSearch\IdUrlParserInterface;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\AddressTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\FallbackType;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\LanguagesTransformer;
use CultuurNet\UDB3\Search\JsonDocument\CompositeJsonTransformer;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerLogger;

final class PlaceTransformer implements JsonTransformer
{
    /**
     * @var CompositeJsonTransformer
     */
    private $compositeTransformer;

    /**
     * @param JsonTransformerLogger $logger
     * @param IdUrlParserInterface $idUrlParser
     */
    public function __construct(
        JsonTransformerLogger $logger,
        IdUrlParserInterface $idUrlParser
    ) {
        $this->compositeTransformer = new CompositeJsonTransformer(
            new OfferTransformer(
                $logger,
                $idUrlParser,
                FallbackType::PLACE()
            ),
            new LanguagesTransformer($logger),
            new AddressTransformer($logger, true)
        );
    }

    public function transform(array $from, array $draft = []): array
    {
        return $this->compositeTransformer->transform($from, $draft);
    }
}
