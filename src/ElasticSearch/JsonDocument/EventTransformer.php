<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument;

use CultuurNet\UDB3\Search\ElasticSearch\IdUrlParserInterface;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\FallbackType;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\GeoInformationTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\MetadataTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\PerformersTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\RelatedLocationTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\RelatedProductionTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\Offer\OfferRegionServiceInterface;
use CultuurNet\UDB3\Search\JsonDocument\CompositeJsonTransformer;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerLogger;
use CultuurNet\UDB3\Search\Offer\OfferType;

final class EventTransformer implements JsonTransformer
{
    /**
     * @var CompositeJsonTransformer
     */
    private $compositeTransformer;

    /**
     * @var GeoInformationTransformer
     */
    private $geoInformationTransformer;

    public function __construct(
        JsonTransformerLogger $logger,
        IdUrlParserInterface $idUrlParser,
        OfferRegionServiceInterface $offerRegionService
    ) {
        $this->compositeTransformer = new CompositeJsonTransformer(
            new OfferTransformer(
                $logger,
                $idUrlParser,
                FallbackType::event()
            ),
            new RelatedLocationTransformer(
                $logger,
                $idUrlParser,
                FallbackType::place()
            ),
            new RelatedProductionTransformer(),
            new PerformersTransformer(),
            new MetadataTransformer()
        );

        $this->geoInformationTransformer = new GeoInformationTransformer(OfferType::event(), $offerRegionService);
    }

    public function transform(array $from, array $draft = []): array
    {
        $draft = $this->compositeTransformer->transform($from, $draft);

        if (isset($from['location'])) {
            $draft = $this->geoInformationTransformer->transform($from['location'], $draft);
        }

        return $draft;
    }
}
