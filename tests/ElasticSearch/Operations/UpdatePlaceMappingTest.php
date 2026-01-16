<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use CultuurNet\UDB3\Search\FileReader;
use CultuurNet\UDB3\Search\Json;
use Elastic\Elasticsearch\Client;
use Psr\Log\LoggerInterface;

final class UpdatePlaceMappingTest extends AbstractMappingTestCase
{
    protected function createOperation(Client $client, LoggerInterface $logger): UpdatePlaceMapping
    {
        return new UpdatePlaceMapping($client, $logger);
    }

    protected function getDocumentType(): string
    {
        return 'place';
    }

    protected function getExpectedMappingBody(): array
    {
        return Json::decodeAssociatively(
            FileReader::read(__DIR__ . '/../../../src/ElasticSearch/Operations/json/mapping_place.json')
        );
    }

    protected function runOperation(string $indexName): void
    {
        $this->operation->run($indexName, $this->getDocumentType());
    }
}
