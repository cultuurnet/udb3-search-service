<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\ElasticSearch\IdUrlParserInterface;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;

final class DeparturePlacesTransformer implements JsonTransformer
{
    private IdUrlParserInterface $idUrlParser;

    public function __construct(IdUrlParserInterface $idUrlParser)
    {
        $this->idUrlParser = $idUrlParser;
    }

    public function transform(array $from, array $draft = []): array
    {
        if (!isset($from['departurePlaces']) || !is_array($from['departurePlaces'])) {
            return $draft;
        }

        $draft['departurePlaces'] = array_map(
            fn (string $iri): string => $this->idUrlParser->getIdFromUrl($iri),
            $from['departurePlaces']
        );

        return $draft;
    }
}
