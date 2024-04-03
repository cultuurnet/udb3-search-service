<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Organizer;

use CultuurNet\UDB3\Search\ElasticSearch\AbstractElasticSearchQueryBuilder;
use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchPagedResultSetFactoryInterface;
use CultuurNet\UDB3\Search\ElasticSearch\HasElasticSearchClient;
use CultuurNet\UDB3\Search\Organizer\OrganizerQueryBuilderInterface;
use CultuurNet\UDB3\Search\Organizer\OrganizerSearchServiceInterface;
use CultuurNet\UDB3\Search\PagedResultSet;
use Elasticsearch\Client;

final class ElasticSearchOrganizerSearchService implements OrganizerSearchServiceInterface
{
    use HasElasticSearchClient;

    /**
     * @var ElasticSearchPagedResultSetFactoryInterface
     */
    private $pagedResultSetFactory;

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
        $parameters = [];
        if ($queryBuilder instanceof AbstractElasticSearchQueryBuilder) {
            $parameters = $queryBuilder->createUrlParameters();
        }

        $response = $this->executeQuery($queryBuilder->build(), $parameters);

        return $this->pagedResultSetFactory->createPagedResultSet(
            $queryBuilder->getLimit()->toInteger(),
            $response
        );
    }
}
