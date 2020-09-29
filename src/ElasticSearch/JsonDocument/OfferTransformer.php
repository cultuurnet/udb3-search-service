<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument;

use CultuurNet\UDB3\Search\ElasticSearch\IdUrlParserInterface;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\CopyJsonCombination;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\AvailabilityTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\CreatedAndModifiedTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\CreatorTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\DuplicateFlagTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\FallbackType;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\IdentifierTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\LabelsTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\NameTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\OriginalEncodedJsonLdTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\RelatedOrganizerTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\TermsTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\TypicalAgeRangeTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\WorkflowStatusTransformer;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerLogger;

class OfferTransformer extends CopyJsonCombination
{
    /**
     * @param JsonTransformerLogger $logger
     * @param IdUrlParserInterface $idUrlParser
     * @param FallbackType $fallbackType
     */
    public function __construct(
        JsonTransformerLogger $logger,
        IdUrlParserInterface $idUrlParser,
        FallbackType $fallbackType
    ) {
        parent::__construct(
            new IdentifierTransformer(
                $logger,
                $idUrlParser,
                $fallbackType,
                false
            ),
            new NameTransformer($logger),
            new AvailabilityTransformer($logger),
            new TermsTransformer(),
            new TypicalAgeRangeTransformer(),
            new RelatedOrganizerTransformer(
                $logger,
                $idUrlParser,
                FallbackType::ORGANIZER()
            ),
            new CreatorTransformer($logger),
            new CreatedAndModifiedTransformer($logger),
            new LabelsTransformer(),
            new WorkflowStatusTransformer($logger),
            new DuplicateFlagTransformer(),
            new OriginalEncodedJsonLdTransformer()
        );
    }
}
