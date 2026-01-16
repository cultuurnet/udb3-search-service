<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Offer;

use CultuurNet\UDB3\Search\ElasticSearch\AbstractElasticSearchQueryBuilder;
use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchPagedResultSetFactoryInterface;
use CultuurNet\UDB3\Search\ElasticSearch\HasElasticSearchClient;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;
use CultuurNet\UDB3\Search\Offer\OfferSearchServiceInterface;
use CultuurNet\UDB3\Search\PagedResultSet;
use Elastic\Elasticsearch\ClientInterface;

final class ElasticSearchOfferSearchService implements OfferSearchServiceInterface
{
    use HasElasticSearchClient;

    private ElasticSearchPagedResultSetFactoryInterface $pagedResultSetFactory;

    public function __construct(
        ClientInterface $elasticSearchClient,
        string $indexName,
        string $documentType,
        ElasticSearchPagedResultSetFactoryInterface $pagedResultSetFactory
    ) {
        $this->elasticSearchClient = $elasticSearchClient;
        $this->indexName = $indexName;
        $this->documentType = $documentType;
        $this->pagedResultSetFactory = $pagedResultSetFactory;
    }

    public function search(OfferQueryBuilderInterface $queryBuilder): PagedResultSet
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
