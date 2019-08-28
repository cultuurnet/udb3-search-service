<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\Organizer;

use CultuurNet\UDB3\Search\ElasticSearch\IdUrlParserInterface;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\CopyJsonAddress;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\CopyJsonCreator;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\CopyJsonIdentifier;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\CopyJsonLabels;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\CopyJsonLanguages;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\CopyJsonName;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\CopyJsonUrl;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\CopyJsonWorkflowStatus;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\CopyOriginalEncodedJsonLd;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components\FallbackType;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\CopyJsonCombination;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Logging\CopyJsonLoggerInterface;
use CultuurNet\UDB3\Search\Organizer\OrganizerJsonDocumentLanguageAnalyzer;

class CopyJsonOrganizer extends CopyJsonCombination
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
            new CopyJsonIdentifier(
                $logger,
                $idUrlParser,
                FallbackType::ORGANIZER()
            ),
            new CopyJsonName($logger),
            new CopyJsonLanguages(
                new OrganizerJsonDocumentLanguageAnalyzer()
            ),
            new CopyJsonAddress($logger, false),
            new CopyJsonCreator($logger),
            new CopyJsonLabels(),
            new CopyJsonUrl(),
            new CopyJsonWorkflowStatus($logger, 'ACTIVE'),
            new CopyOriginalEncodedJsonLd()
        );
    }
}
