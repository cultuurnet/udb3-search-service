<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use Elasticsearch\Client;
use Psr\Log\LoggerInterface;

class UpdateOrganizerMappingTest extends AbstractMappingTestCase
{
    /**
     * @return UpdateOrganizerMapping
     */
    protected function createOperation(Client $client, LoggerInterface $logger)
    {
        return new UpdateOrganizerMapping($client, $logger);
    }

    /**
     * @return string
     */
    protected function getDocumentType()
    {
        return 'organizer';
    }

    /**
     * @return array
     */
    protected function getExpectedMappingBody()
    {
        return json_decode(
            file_get_contents(__DIR__ . '/../../../src/ElasticSearch/Operations/json/mapping_organizer.json'),
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
