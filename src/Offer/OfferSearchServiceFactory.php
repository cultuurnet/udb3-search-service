<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

use CultuurNet\UDB3\Search\ElasticSearch\Aggregation\AggregationTransformerInterface;
use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchPagedResultSetFactory;
use CultuurNet\UDB3\Search\ElasticSearch\Offer\ElasticSearchOfferSearchService;
use CultuurNet\UDB3\Search\ElasticSearch\Client\ElasticSearchClient;

final class OfferSearchServiceFactory
{
    private ElasticSearchClient $client;

    private AggregationTransformerInterface $aggregationTransformer;

    public function __construct(ElasticSearchClient $client, AggregationTransformerInterface $aggregationTransformer)
    {
        $this->client = $client;
        $this->aggregationTransformer = $aggregationTransformer;
    }

    public function createFor(string $readIndex, string $documentType): OfferSearchServiceInterface
    {
        $pagedResultSetFactory = new ElasticSearchPagedResultSetFactory(
            $this->aggregationTransformer
        );

        return new ElasticSearchOfferSearchService(
            $this->client,
            $readIndex,
            $documentType,
            $pagedResultSetFactory
        );
    }
}
