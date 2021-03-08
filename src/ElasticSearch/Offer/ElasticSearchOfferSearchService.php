<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Offer;

use CultuurNet\UDB3\Search\ElasticSearch\AbstractElasticSearchQueryBuilder;
use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchPagedResultSetFactoryInterface;
use CultuurNet\UDB3\Search\ElasticSearch\HasElasticSearchClient;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;
use CultuurNet\UDB3\Search\Offer\OfferSearchServiceInterface;
use CultuurNet\UDB3\Search\PagedResultSet;
use Elasticsearch\Client;
use ValueObjects\StringLiteral\StringLiteral;

final class ElasticSearchOfferSearchService implements OfferSearchServiceInterface
{
    use HasElasticSearchClient;

    /**
     * @var ElasticSearchPagedResultSetFactoryInterface
     */
    private $pagedResultSetFactory;

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
