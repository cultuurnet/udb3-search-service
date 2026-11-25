<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Offer;

use CultuurNet\UDB3\Search\ElasticSearch\AbstractElasticSearchQueryBuilder;
use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchPagedResultSetFactoryInterface;
use CultuurNet\UDB3\Search\ElasticSearch\HasElasticSearchClient;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;
use CultuurNet\UDB3\Search\Offer\OfferSearchServiceInterface;
use CultuurNet\UDB3\Search\Offer\PlaceEventCount;
use CultuurNet\UDB3\Search\Offer\PlaceEventCounts;
use CultuurNet\UDB3\Search\PagedResultSet;
use CultuurNet\UDB3\Search\ElasticSearch\Client\ElasticSearchClient;

final class ElasticSearchOfferSearchService implements OfferSearchServiceInterface
{
    use HasElasticSearchClient;

    private ElasticSearchPagedResultSetFactoryInterface $pagedResultSetFactory;

    public function __construct(
        ElasticSearchClient $elasticSearchClient,
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

    public function countEventsPerPlace(OfferQueryBuilderInterface $queryBuilder): PlaceEventCounts
    {
        $parameters = [];
        if ($queryBuilder instanceof AbstractElasticSearchQueryBuilder) {
            $parameters = $queryBuilder->createUrlParameters();
        }

        $response = $this->executeQuery($queryBuilder->build(), $parameters);

        $buckets = $response['aggregations'][ElasticSearchOfferQueryBuilder::PLACE_EVENT_COUNTS]['places']['buckets'] ?? [];

        $places = array_map(
            static function (array $bucket): PlaceEventCount {
                $source = $bucket['place']['hits']['hits'][0]['_source'];

                return new PlaceEventCount(
                    $source['location']['@id'],
                    $source['location']['name'] ?? [],
                    (float) $source['geo_point']['lat'],
                    (float) $source['geo_point']['lon'],
                    (int) $bucket['doc_count']
                );
            },
            $buckets
        );

        return new PlaceEventCounts((int) $response['hits']['total']['value'], ...$places);
    }
}
