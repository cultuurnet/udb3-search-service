<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations\ElasticSearch5;

use CultuurNet\UDB3\Search\ElasticSearch\Operations\AbstractMappingTestCase;
use CultuurNet\UDB3\Search\ElasticSearch\Operations\UpdateRegionMapping;
use CultuurNet\UDB3\Search\ElasticSearch5Test;
use Elasticsearch\Client;
use Psr\Log\LoggerInterface;

final class UpdateRegionMappingTest extends AbstractMappingTestCase implements ElasticSearch5Test
{
    protected function createOperation(Client $client, LoggerInterface $logger): UpdateRegionMapping
    {
        return new UpdateRegionMapping($client, $logger);
    }

    protected function getDocumentType(): string
    {
        return 'region';
    }

    protected function getExpectedMappingBody(): array
    {
        return [
            'properties' => [
                'location' => [
                    'type' => 'geo_shape',
                ],
            ],
        ];
    }

    protected function runOperation(string $indexName): void
    {
        $this->operation->run($indexName, $this->getDocumentType());
    }

    /**
     * @test
     */
    public function it_updates_the_mapping_with_type_on_es5(): void
    {
        $indexName = 'mock';
        $documentType = $this->getDocumentType();

        $this->indices->expects($this->once())
            ->method('putMapping')
            ->with([
                'index' => $indexName,
                'type' => $documentType,
                'body' => $this->getExpectedMappingBody(),
            ]);

        $this->logger->expects($this->once())
            ->method('info')
            ->with("Mapping for type {$documentType} updated.");

        $this->operation->enableElasticSearch5CompatibilityMode();
        $this->operation->run($indexName, $documentType);
    }
}
