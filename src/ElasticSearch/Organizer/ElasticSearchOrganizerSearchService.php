<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\Organizer;

use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchPagedResultSetFactoryInterface;
use CultuurNet\UDB3\Search\ElasticSearch\HasElasticSearchClient;
use CultuurNet\UDB3\Search\Organizer\OrganizerQueryBuilderInterface;
use CultuurNet\UDB3\Search\Organizer\OrganizerSearchServiceInterface;
use CultuurNet\UDB3\Search\PagedResultSet;
use Elasticsearch\Client;
use ValueObjects\Number\Natural;
use ValueObjects\StringLiteral\StringLiteral;

class ElasticSearchOrganizerSearchService implements OrganizerSearchServiceInterface
{
    use HasElasticSearchClient;

    /**
     * @var ElasticSearchPagedResultSetFactoryInterface
     */
    private $pagedResultSetFactory;

    /**
     * @param Client $elasticSearchClient
     * @param StringLiteral $indexName
     * @param StringLiteral $documentType
     * @param ElasticSearchPagedResultSetFactoryInterface $pagedResultSetFactory
     */
    public function __construct(
        Client $elasticSearchClient,
        StringLiteral $indexName,
        StringLiteral $documentType,
        ElasticSearchPagedResultSetFactoryInterface $pagedResultSetFactory
    ) {
        $this->elasticSearchClient = $elasticSearchClient;
        $this->indexName = $indexName;
        $this->documentType = $documentType;
        $this->pagedResultSetFactory = $pagedResultSetFactory;
    }

    /**
     * @param OrganizerQueryBuilderInterface $queryBuilder
     * @return PagedResultSet
     */
    public function search(OrganizerQueryBuilderInterface $queryBuilder)
    {
        /* @var \ONGR\ElasticsearchDSL\Search $search */
        $search = $queryBuilder->build();

        $response = $this->executeQuery($search->toArray());

        return $this->pagedResultSetFactory->createPagedResultSet(
            new Natural($search->getSize()),
            $response
        );
    }
}
