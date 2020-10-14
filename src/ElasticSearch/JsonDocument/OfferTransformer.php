<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument;

use CultuurNet\UDB3\Search\ElasticSearch\IdUrlParserInterface;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\AudienceTypeTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\AvailabilityTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\CalendarTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\CreatedAndModifiedTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\CreatorTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\DescriptionTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\DuplicateFlagTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\FallbackType;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\IdentifierTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\LabelsTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\LanguagesTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\MediaObjectsTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\NameTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\OriginalEncodedJsonLdTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\PriceInfoTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\ProductionCollapseValueTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\RelatedOrganizerTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\TermsTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\TypicalAgeRangeTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\WorkflowStatusTransformer;
use CultuurNet\UDB3\Search\JsonDocument\CompositeJsonTransformer;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerLogger;

final class OfferTransformer implements JsonTransformer
{
    /**
     * @var CompositeJsonTransformer
     */
    private $compositeTransformer;

    public function __construct(
        JsonTransformerLogger $logger,
        IdUrlParserInterface $idUrlParser,
        FallbackType $fallbackType
    ) {
        $this->compositeTransformer = new CompositeJsonTransformer(
            new IdentifierTransformer(
                $logger,
                $idUrlParser,
                $fallbackType,
                false
            ),
            new LanguagesTransformer($logger, true),
            new NameTransformer($logger),
            new DescriptionTransformer(),
            new CalendarTransformer($logger),
            new AvailabilityTransformer($logger),
            new TermsTransformer(true, true),
            new TypicalAgeRangeTransformer(),
            new PriceInfoTransformer(),
            new AudienceTypeTransformer(),
            new MediaObjectsTransformer(),
            new RelatedOrganizerTransformer(
                $logger,
                $idUrlParser,
                FallbackType::ORGANIZER()
            ),
            new CreatorTransformer($logger),
            new CreatedAndModifiedTransformer($logger),
            new LabelsTransformer(true),
            new WorkflowStatusTransformer($logger),
            new DuplicateFlagTransformer(),
            new ProductionCollapseValueTransformer($idUrlParser),
            new OriginalEncodedJsonLdTransformer()
        );
    }

    public function transform(array $from, array $draft = []): array
    {
        return $this->compositeTransformer->transform($from, $draft);
    }
}
