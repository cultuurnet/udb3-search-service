<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use CultuurNet\UDB3\Search\FileReader;
use CultuurNet\UDB3\Search\Json;
use Elastic\Elasticsearch\ClientInterface;
use Psr\Log\LoggerInterface;

final class UpdateOrganizerMappingTest extends AbstractMappingTestCase
{
    protected function createOperation(ClientInterface $client, LoggerInterface $logger): UpdateOrganizerMapping
    {
        return new UpdateOrganizerMapping($client, $logger);
    }

    protected function getDocumentType(): string
    {
        return 'organizer';
    }

    protected function getExpectedMappingBody(): array
    {
        return Json::decodeAssociatively(
            FileReader::read(__DIR__ . '/../../../src/ElasticSearch/Operations/json/mapping_organizer.json')
        );
    }

    protected function runOperation(string $indexName): void
    {
        $this->operation->run($indexName, $this->getDocumentType());
    }
}
