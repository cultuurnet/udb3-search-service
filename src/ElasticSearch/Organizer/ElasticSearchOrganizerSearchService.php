<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Organizer;


use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchPagedResultSetFactoryInterface;
use CultuurNet\UDB3\Search\ElasticSearch\HasElasticSearchClient;
use CultuurNet\UDB3\Search\Organizer\OrganizerQueryBuilderInterface;
use CultuurNet\UDB3\Search\Organizer\OrganizerSearchServiceInterface;
use CultuurNet\UDB3\Search\PagedResultSet;
use Elasticsearch\Client;

final class ElasticSearchOrganizerSearchService implements OrganizerSearchServiceInterface
{
    use HasElasticSearchClient;

    private ElasticSearchPagedResultSetFactoryInterface $pagedResultSetFactory;

    public function __construct(
        Client $elasticSearchClient,
        string $indexName,
        string $documentType,
        ElasticSearchPagedResultSetFactoryInterface $pagedResultSetFactory
    ) {
        $this->elasticSearchClient = $elasticSearchClient;
        $this->indexName = $indexName;
        $this->documentType = $documentType;
        $this->pagedResultSetFactory = $pagedResultSetFactory;
    }

    public function search(OrganizerQueryBuilderInterface $queryBuilder): PagedResultSet
    {
        $response = $this->executeQuery($queryBuilder->build(), $queryBuilder->createUrlParameters());

        return $this->pagedResultSetFactory->createPagedResultSet(
            $queryBuilder->getLimit()->toInteger(),
            $response
        );
    }
}
