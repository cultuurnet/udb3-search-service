<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

use CultuurNet\UDB3\Search\ElasticSearch\Aggregation\AggregationTransformerInterface;
use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchPagedResultSetFactory;
use CultuurNet\UDB3\Search\ElasticSearch\Offer\ElasticSearchOfferSearchService;
use Elastic\Elasticsearch\ClientInterface;

final class OfferSearchServiceFactory
{
    private ClientInterface $client;

    private AggregationTransformerInterface $aggregationTransformer;

    public function __construct(ClientInterface $client, AggregationTransformerInterface $aggregationTransformer)
    {
        $this->client = $client;
        $this->aggregationTransformer = $aggregationTransformer;
    }

    public function createFor(string $readIndex, string $documentType): OfferSearchServiceInterface
    {
        return new ElasticSearchOfferSearchService(
            $this->client,
            $readIndex,
            $documentType,
            new ElasticSearchPagedResultSetFactory(
                $this->aggregationTransformer
            )
        );
    }
}
