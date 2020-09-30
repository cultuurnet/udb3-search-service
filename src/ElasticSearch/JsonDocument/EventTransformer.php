<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument;

use CultuurNet\UDB3\Search\ElasticSearch\IdUrlParserInterface;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\CopyJsonInterface;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\FallbackType;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\LanguagesTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\RelatedLocationTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\RelatedProductionTransformer;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerLogger;

class EventTransformer implements CopyJsonInterface
{
    /**
     * @var OfferTransformer
     */
    private $offerTransformer;

    /**
     * @var RelatedLocationTransformer
     */
    private $relatedLocationTransformer;

    /**
     * @var RelatedProductionTransformer
     */
    private $relatedProductionTransformer;

    /**
     * @var LanguagesTransformer
     */
    private $languagesTransformer;

    /**
     * @param JsonTransformerLogger $logger
     * @param IdUrlParserInterface $idUrlParser
     */
    public function __construct(
        JsonTransformerLogger $logger,
        IdUrlParserInterface $idUrlParser
    ) {
        $this->offerTransformer = new OfferTransformer(
            $logger,
            $idUrlParser,
            FallbackType::EVENT()
        );

        $this->relatedLocationTransformer = new RelatedLocationTransformer(
            $logger,
            $idUrlParser,
            FallbackType::PLACE()
        );

        $this->relatedProductionTransformer = new RelatedProductionTransformer();

        $this->languagesTransformer = new LanguagesTransformer($logger);
    }

    /**
     * @param \stdClass $from
     * @param \stdClass $to
     */
    public function copy(\stdClass $from, \stdClass $to)
    {
        $this->offerTransformer->copy($from, $to);

        $this->relatedLocationTransformer->copy($from, $to);

        $this->relatedProductionTransformer->copy($from, $to);

        $this->languagesTransformer->copy($from, $to);
    }
}
