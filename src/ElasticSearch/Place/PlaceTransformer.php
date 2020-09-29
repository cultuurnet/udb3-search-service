<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\Place;

use CultuurNet\UDB3\Search\ElasticSearch\IdUrlParserInterface;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\AddressTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\LanguagesTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\FallbackType;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\CopyJsonCombination;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\OfferTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Logging\CopyJsonLoggerInterface;

class PlaceTransformer extends CopyJsonCombination
{
    /**
     * @param CopyJsonLoggerInterface $logger
     * @param IdUrlParserInterface $idUrlParser
     */
    public function __construct(
        CopyJsonLoggerInterface $logger,
        IdUrlParserInterface $idUrlParser
    ) {
        parent::__construct(
            new OfferTransformer(
                $logger,
                $idUrlParser,
                FallbackType::PLACE()
            ),
            new LanguagesTransformer($logger),
            new AddressTransformer($logger, true)
        );
    }
}
