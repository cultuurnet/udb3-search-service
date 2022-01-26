<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use CultuurNet\UDB3\Search\Json;
use Elasticsearch\Client;
use Psr\Log\LoggerInterface;

final class UpdatePlaceMappingTest extends AbstractMappingTestCase
{
    /**
     * @return UpdatePlaceMapping
     */
    protected function createOperation(Client $client, LoggerInterface $logger)
    {
        return new UpdatePlaceMapping($client, $logger);
    }

    /**
     * @return string
     */
    protected function getDocumentType()
    {
        return 'place';
    }

    /**
     * @return array
     */
    protected function getExpectedMappingBody()
    {
        return Json::decodeAssociatively(
            file_get_contents(__DIR__ . '/../../../src/ElasticSearch/Operations/json/mapping_place.json')
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
