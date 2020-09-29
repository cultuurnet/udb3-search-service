<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson;

use CultuurNet\UDB3\Search\ElasticSearch\IdUrlParserInterface;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\AvailabilityTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\CreatorTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\DuplicateFlagTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\IdentifierTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\LabelsTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\NameTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\TermsTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\TypicalAgeRangeTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\WorkflowStatusTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\OriginalEncodedJsonLdTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\FallbackType;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Logging\CopyJsonLoggerInterface;

class OfferTransformer extends CopyJsonCombination
{
    /**
     * @param CopyJsonLoggerInterface $logger
     * @param IdUrlParserInterface $idUrlParser
     * @param FallbackType $fallbackType
     */
    public function __construct(
        CopyJsonLoggerInterface $logger,
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
