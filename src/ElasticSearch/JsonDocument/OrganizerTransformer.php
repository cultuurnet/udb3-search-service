<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument;

use CultuurNet\UDB3\Search\ElasticSearch\IdUrlParserInterface;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\AddressTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\CompletenessTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\ContributorsTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\CreatedAndModifiedTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\CreatorTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\DescriptionTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\EducationalDescriptionTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\FallbackType;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\GeoInformationTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\IdentifierTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\ImagesTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\LabelsTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\LanguagesTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\NameTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\OriginalEncodedJsonLdTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\UrlTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\WorkflowStatusTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\Region\RegionServiceInterface;
use CultuurNet\UDB3\Search\JsonDocument\CompositeJsonTransformer;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerLogger;

final class OrganizerTransformer implements JsonTransformer
{
    private CompositeJsonTransformer $compositeTransformer;

    public function __construct(
        JsonTransformerLogger $logger,
        IdUrlParserInterface $idUrlParser,
        RegionServiceInterface $regionService
    ) {
        $this->compositeTransformer = new CompositeJsonTransformer(
            new IdentifierTransformer(
                $logger,
                $idUrlParser,
                FallbackType::organizer(),
                false
            ),
            new NameTransformer($logger),
            new DescriptionTransformer(),
            new EducationalDescriptionTransformer(),
            new LanguagesTransformer($logger, false),
            new AddressTransformer($logger, false),
            new ImagesTransformer(),
            new CreatorTransformer($logger),
            new CreatedAndModifiedTransformer($logger),
            new LabelsTransformer(false),
            new UrlTransformer(),
            new WorkflowStatusTransformer($logger, 'ACTIVE'),
            new ContributorsTransformer(),
            new GeoInformationTransformer($regionService),
            new CompletenessTransformer(),
            new OriginalEncodedJsonLdTransformer()
        );
    }

    public function transform(array $from, array $draft = []): array
    {
        return $this->compositeTransformer->transform($from, $draft);
    }
}
