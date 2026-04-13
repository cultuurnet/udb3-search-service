<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use Elasticsearch\Client;
use Psr\Log\LoggerInterface;

final class UpdateRegionMappingTest extends AbstractOperationTestCase
{
    protected function createOperation(Client $client, LoggerInterface $logger): UpdateRegionMapping
    {
        return new UpdateRegionMapping($client, $logger);
    }

    private function getExpectedMappingBody(): array
    {
        return [
            'properties' => [
                'location' => [
                    'type' => 'geo_shape',
                ],
            ],
        ];
    }

    /**
     * @test
     */
    public function it_updates_the_mapping_without_type(): void
    {
        $indexName = 'mock';
        $documentType = 'region';

        $this->indices->expects($this->once())
            ->method('putMapping')
            ->with([
                'index' => $indexName,
                'body' => $this->getExpectedMappingBody(),
            ]);

        $this->logger->expects($this->once())
            ->method('info')
            ->with("Mapping for type {$documentType} updated.");

        $this->operation->run($indexName, $documentType);
    }
}
