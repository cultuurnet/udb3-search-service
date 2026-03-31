<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use CultuurNet\UDB3\Search\FileReader;
use CultuurNet\UDB3\Search\Json;
use Elasticsearch\Client;
use Psr\Log\LoggerInterface;

final class UpdateUdb3CoreMappingTest extends AbstractOperationTestCase
{
    protected function createOperation(Client $client, LoggerInterface $logger): UpdateUdb3CoreMapping
    {
        return new UpdateUdb3CoreMapping($client, $logger);
    }

    /**
     * @test
     */
    public function it_updates_the_mapping_without_type(): void
    {
        $indexName = 'mock';
        $documentType = 'udb3_core';
        $mappingBody = Json::decodeAssociatively(
            FileReader::read(__DIR__ . '/../../../src/ElasticSearch/Operations/json/mapping_udb3_core.json')
        );

        $this->indices->expects($this->once())
            ->method('putMapping')
            ->with(
                [
                    'index' => $indexName,
                    'body' => $mappingBody,
                ]
            );

        $this->logger->expects($this->once())
            ->method('info')
            ->with("Mapping for type {$documentType} updated.");

        $this->operation->run($indexName, $documentType);
    }
}
