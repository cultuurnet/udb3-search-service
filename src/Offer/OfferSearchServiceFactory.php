<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

use CultuurNet\UDB3\Search\ElasticSearch\Aggregation\AggregationTransformerInterface;
use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchPagedResultSetFactory;
use CultuurNet\UDB3\Search\ElasticSearch\Offer\ElasticSearchOfferSearchService;
use Elasticsearch\Client;

final class OfferSearchServiceFactory
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var AggregationTransformerInterface
     */
    private $aggregationTransformer;

    public function __construct(Client $client, AggregationTransformerInterface $aggregationTransformer)
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
