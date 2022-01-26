<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use CultuurNet\UDB3\Search\Json;
use Elasticsearch\Client;
use Psr\Log\LoggerInterface;

final class UpdateEventMappingTest extends AbstractMappingTestCase
{
    protected function createOperation(Client $client, LoggerInterface $logger): UpdateEventMapping
    {
        return new UpdateEventMapping($client, $logger);
    }

    protected function getDocumentType(): string
    {
        return 'event';
    }

    protected function getExpectedMappingBody(): array
    {
        return Json::decodeAssociatively(
            file_get_contents(__DIR__ . '/../../../src/ElasticSearch/Operations/json/mapping_event.json')
        );
    }

    protected function runOperation(string $indexName): void
    {
        $this->operation->run($indexName, $this->getDocumentType());
    }
}
