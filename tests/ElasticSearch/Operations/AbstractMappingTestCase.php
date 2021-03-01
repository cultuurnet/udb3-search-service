<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

abstract class AbstractMappingTestCase extends AbstractOperationTestCase
{
    /**
     * @return string
     */
    abstract protected function getDocumentType();

    /**
     * @return array
     */
    abstract protected function getExpectedMappingBody();

    /**
     * @param string $indexName
     */
    abstract protected function runOperation($indexName);

    /**
     * @test
     */
    public function it_updates_the_mapping_of_the_given_document_type_with_the_expected_mapping_body()
    {
        $indexName = 'mock';
        $documentType = $this->getDocumentType();
        $mappingBody = $this->getExpectedMappingBody();

        $this->indices->expects($this->once())
            ->method('putMapping')
            ->with(
                [
                    'index' => $indexName,
                    'type' => $documentType,
                    'body' => $mappingBody,
                ]
            );

        $this->logger->expects($this->once())
            ->method('info')
            ->with("Mapping for type {$documentType} updated.");

        $this->runOperation($indexName);
    }
}
