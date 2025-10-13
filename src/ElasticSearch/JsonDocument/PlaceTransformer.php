<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument;

use CultuurNet\UDB3\Search\ElasticSearch\IdUrlParserInterface;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\AddressTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\UniqueAddressTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\FallbackType;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\GeoInformationTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\MetadataTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\Region\RegionServiceInterface;
use CultuurNet\UDB3\Search\JsonDocument\CompositeJsonTransformer;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerLogger;

final class PlaceTransformer implements JsonTransformer
{
    private CompositeJsonTransformer $compositeTransformer;

    public function __construct(
        JsonTransformerLogger $logger,
        IdUrlParserInterface $idUrlParser,
        RegionServiceInterface $regionService,
        bool $duplicatePlacesPerUser
    ) {
        $this->compositeTransformer = new CompositeJsonTransformer(
            new OfferTransformer(
                $logger,
                $idUrlParser,
                FallbackType::place()
            ),
            new AddressTransformer($logger, true),
            new UniqueAddressTransformer($duplicatePlacesPerUser),
            new GeoInformationTransformer($regionService),
            new MetadataTransformer()
        );
    }

    public function transform(array $from, array $draft = []): array
    {
        return $this->compositeTransformer->transform($from, $draft);
    }
}
