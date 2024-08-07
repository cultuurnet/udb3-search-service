<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\ElasticSearch\IdUrlParserInterface;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerLogger;

final class RelatedLocationTransformer implements JsonTransformer
{
    private IdUrlParserInterface $idUrlParser;

    private IdentifierTransformer $identifierTransformer;

    private NameTransformer $nameTransformer;

    private TermsTransformer $termsTransformer;

    private LabelsTransformer $labelsTransformer;

    private AddressTransformer $addressTransformer;

    private JsonTransformerLogger $logger;

    public function __construct(
        JsonTransformerLogger $logger,
        IdUrlParserInterface $idUrlParser,
        FallbackType $fallbackType
    ) {
        $this->logger = $logger;
        $this->idUrlParser = $idUrlParser;

        $this->identifierTransformer = new IdentifierTransformer(
            $logger,
            $idUrlParser,
            $fallbackType,
            true
        );

        $this->nameTransformer = new NameTransformer($logger);

        $this->termsTransformer = new TermsTransformer(false, false);

        $this->labelsTransformer = new LabelsTransformer(false);

        $this->addressTransformer = new AddressTransformer($logger, true);
    }

    public function transform(array $from, array $draft = []): array
    {
        if (!isset($from['location'])) {
            $this->logger->logMissingExpectedField('location');
            return $draft;
        }

        $draft['location'] ??= [];
        $draft['location'] = $this->identifierTransformer->transform($from['location'], $draft['location']);

        if (isset($from['location']['duplicatedBy'])) {
            $idsOfDuplicates = array_map(
                fn (string $iriOfDuplicate) => $this->idUrlParser->getIdFromUrl($iriOfDuplicate),
                $from['location']['duplicatedBy']
            );

            $draft['location']['id'] = array_merge([$draft['location']['id']], $idsOfDuplicates);
        }

        $draft['location'] = $this->nameTransformer->transform($from['location'], $draft['location']);
        $draft['location'] = $this->termsTransformer->transform($from['location'], $draft['location']);
        $draft['location'] = $this->labelsTransformer->transform($from['location'], $draft['location']);

        $draft = $this->addressTransformer->transform($from['location'], $draft);

        return $draft;
    }
}
