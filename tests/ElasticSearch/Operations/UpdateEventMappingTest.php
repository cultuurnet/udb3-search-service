<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use Elasticsearch\Client;
use Psr\Log\LoggerInterface;

class UpdateEventMappingTest extends AbstractMappingTestCase
{
    /**
     * @return UpdateEventMapping
     */
    protected function createOperation(Client $client, LoggerInterface $logger)
    {
        return new UpdateEventMapping($client, $logger);
    }

    /**
     * @return string
     */
    protected function getDocumentType()
    {
        return 'event';
    }

    /**
     * @return array
     */
    protected function getExpectedMappingBody()
    {
        return json_decode(
            file_get_contents(__DIR__ . '/../../../src/ElasticSearch/Operations/json/mapping_event.json'),
            true
        );
    }

    /**
     * @param string $indexName
     */
    protected function runOperation($indexName)
    {
        $this->operation->run($indexName, $this->getDocumentType());
    }
}
