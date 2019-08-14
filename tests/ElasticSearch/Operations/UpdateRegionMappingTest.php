<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use Elasticsearch\Client;
use Psr\Log\LoggerInterface;

class UpdateRegionMappingTest extends AbstractMappingTestCase
{
    /**
     * @param Client $client
     * @param LoggerInterface $logger
     * @return UpdateRegionMapping
     */
    protected function createOperation(Client $client, LoggerInterface $logger)
    {
        return new UpdateRegionMapping($client, $logger);
    }

    /**
     * @return string
     */
    protected function getDocumentType()
    {
        return 'region';
    }

    /**
     * @return array
     */
    protected function getExpectedMappingBody()
    {
        return [
            "properties" => [
                "location" => [
                    "type" => "geo_shape",
                ],
            ],
        ];
    }

    /**
     * @param string $indexName
     */
    protected function runOperation($indexName)
    {
        $this->operation->run($indexName, $this->getDocumentType());
    }
}
