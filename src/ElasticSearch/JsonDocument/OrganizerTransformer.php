<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument;

use CultuurNet\UDB3\Search\ElasticSearch\IdUrlParserInterface;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\CopyJsonCombination;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Logging\CopyJsonLoggerInterface;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\AddressTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\CreatedAndModifiedTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\CreatorTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\FallbackType;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\IdentifierTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\LabelsTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\LanguagesTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\NameTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\OriginalEncodedJsonLdTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\UrlTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties\WorkflowStatusTransformer;

class OrganizerTransformer extends CopyJsonCombination
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
            new IdentifierTransformer(
                $logger,
                $idUrlParser,
                FallbackType::ORGANIZER(),
                false
            ),
            new NameTransformer($logger),
            new LanguagesTransformer($logger),
            new AddressTransformer($logger, false),
            new CreatorTransformer($logger),
            new CreatedAndModifiedTransformer($logger),
            new LabelsTransformer(),
            new UrlTransformer(),
            new WorkflowStatusTransformer($logger, 'ACTIVE'),
            new OriginalEncodedJsonLdTransformer()
        );
    }
}
