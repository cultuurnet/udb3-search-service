<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\ElasticSearch\IdUrlParserInterface;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerLogger;

final class RelatedOrganizerTransformer implements JsonTransformer
{
    /**
     * @var IdentifierTransformer
     */
    private $identifierTransformer;

    /**
     * @var NameTransformer
     */
    private $nameTransformer;

    /**
     * @var LabelsTransformer
     */
    private $labelsTransformer;

    public function __construct(
        JsonTransformerLogger $logger,
        IdUrlParserInterface $idUrlParser,
        FallbackType $fallbackType
    ) {
        $this->identifierTransformer = new IdentifierTransformer(
            $logger,
            $idUrlParser,
            $fallbackType,
            false
        );

        $this->nameTransformer = new NameTransformer($logger);

        $this->labelsTransformer = new LabelsTransformer();
    }

    public function transform(array $from, array $draft = []): array
    {
        if (!isset($from['organizer'])) {
            return $draft;
        }

        $draft['organizer'] = $draft['organizer'] ?? [];
        $draft['organizer'] = $this->identifierTransformer->transform($from['organizer'], $draft['organizer']);
        $draft['organizer'] = $this->nameTransformer->transform($from['organizer'], $draft['organizer']);
        $draft['organizer'] = $this->labelsTransformer->transform($from['organizer'], $draft['organizer']);

        return $draft;
    }
}
