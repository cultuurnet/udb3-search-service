<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\Organizer;

use CultuurNet\UDB3\Search\ElasticSearch\IdUrlParserInterface;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\AddressTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\CreatorTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\IdentifierTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\LabelsTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\LanguagesTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\NameTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\UrlTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\WorkflowStatusTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\OriginalEncodedJsonLdTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\FallbackType;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\CopyJsonCombination;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\CreatedAndModifiedTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Logging\CopyJsonLoggerInterface;

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
