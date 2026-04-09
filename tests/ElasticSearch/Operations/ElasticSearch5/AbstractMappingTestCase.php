<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations\ElasticSearch5;

use CultuurNet\UDB3\Search\ElasticSearch\Operations\AbstractOperationTestCase;
use CultuurNet\UDB3\Search\ElasticSearch5Test;

abstract class AbstractMappingTestCase extends AbstractOperationTestCase implements ElasticSearch5Test
{
    abstract protected function getDocumentType(): string;

    abstract protected function getExpectedMappingBody(): array;

    abstract protected function runOperation(string $indexName): void;

    /**
     * @test
     */
    public function it_updates_the_mapping_of_the_given_document_type_with_the_expected_mapping_body(): void
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

        $this->operation->enableElasticSearch5CompatibilityMode();
        $this->runOperation($indexName);
    }
}
