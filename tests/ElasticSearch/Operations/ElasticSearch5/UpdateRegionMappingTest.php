<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations\ElasticSearch5;

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
}
