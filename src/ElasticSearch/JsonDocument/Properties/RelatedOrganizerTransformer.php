<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\ElasticSearch\IdUrlParserInterface;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerLogger;

final class RelatedOrganizerTransformer implements JsonTransformer
{
    private IdentifierTransformer $identifierTransformer;

    private NameTransformer $nameTransformer;

    private LabelsTransformer $labelsTransformer;

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

        $this->labelsTransformer = new LabelsTransformer(false);
    }

    public function transform(array $from, array $draft = []): array
    {
        if (!isset($from['organizer'])) {
            return $draft;
        }

        $draft['organizer'] ??= [];
        $draft['organizer'] = $this->identifierTransformer->transform($from['organizer'], $draft['organizer']);
        $draft['organizer'] = $this->nameTransformer->transform($from['organizer'], $draft['organizer']);
        $draft['organizer'] = $this->labelsTransformer->transform($from['organizer'], $draft['organizer']);

        return $draft;
    }
}
