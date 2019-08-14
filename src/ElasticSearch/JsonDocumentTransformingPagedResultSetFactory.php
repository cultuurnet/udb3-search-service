<?php

namespace CultuurNet\UDB3\Search\ElasticSearch;

use CultuurNet\UDB3\Search\JsonDocument\JsonDocumentTransformerInterface;
use CultuurNet\UDB3\Search\PagedResultSet;
use ValueObjects\Number\Natural;

class JsonDocumentTransformingPagedResultSetFactory implements ElasticSearchPagedResultSetFactoryInterface
{
    /**
     * @var JsonDocumentTransformerInterface
     */
    private $jsonDocumentTransformer;

    /**
     * @var ElasticSearchPagedResultSetFactoryInterface|null
     */
    private $pagedResultSetFactory;

    /**
     * @param JsonDocumentTransformerInterface $jsonDocumentTransformer
     * @param ElasticSearchPagedResultSetFactoryInterface $pagedResultSetFactory
     */
    public function __construct(
        JsonDocumentTransformerInterface $jsonDocumentTransformer,
        ElasticSearchPagedResultSetFactoryInterface $pagedResultSetFactory
    ) {
        $this->jsonDocumentTransformer = $jsonDocumentTransformer;
        $this->pagedResultSetFactory = $pagedResultSetFactory;
    }

    /**
     * @param Natural $perPage
     * @param array $response
     * @return PagedResultSet
     */
    public function createPagedResultSet(Natural $perPage, array $response)
    {
        $pagedResultSet = $this->pagedResultSetFactory->createPagedResultSet($perPage, $response);

        $documents = [];
        foreach ($pagedResultSet->getResults() as $jsonDocument) {
            $documents[] = $this->jsonDocumentTransformer->transform($jsonDocument);
        }

        return (new PagedResultSet(
            $pagedResultSet->getTotal(),
            $pagedResultSet->getPerPage(),
            $documents
        ))->withFacets(...$pagedResultSet->getFacets());
    }
}
