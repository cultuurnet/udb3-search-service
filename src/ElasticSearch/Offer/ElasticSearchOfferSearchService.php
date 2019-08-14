<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\Offer;

use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchPagedResultSetFactoryInterface;
use CultuurNet\UDB3\Search\ElasticSearch\HasElasticSearchClient;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;
use CultuurNet\UDB3\Search\Offer\OfferSearchServiceInterface;
use CultuurNet\UDB3\Search\PagedResultSet;
use Elasticsearch\Client;
use ValueObjects\Number\Natural;
use ValueObjects\StringLiteral\StringLiteral;

class ElasticSearchOfferSearchService implements OfferSearchServiceInterface
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
     * @param OfferQueryBuilderInterface $queryBuilder
     * @return PagedResultSet
     */
    public function search(OfferQueryBuilderInterface $queryBuilder)
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
