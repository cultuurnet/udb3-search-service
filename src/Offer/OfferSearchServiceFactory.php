<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

use CultuurNet\UDB3\Search\ElasticSearch\Aggregation\AggregationTransformerInterface;
use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchPagedResultSetFactory;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocumentTransformingPagedResultSetFactory;
use CultuurNet\UDB3\Search\ElasticSearch\Offer\ElasticSearchOfferSearchService;
use CultuurNet\UDB3\Search\JsonDocument\PassThroughJsonDocumentTransformer;
use Elasticsearch\Client;
use ValueObjects\StringLiteral\StringLiteral;

class OfferSearchServiceFactory
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
            new StringLiteral($readIndex),
            new StringLiteral($documentType),
            new JsonDocumentTransformingPagedResultSetFactory(
                new PassThroughJsonDocumentTransformer(),
                new ElasticSearchPagedResultSetFactory(
                    $this->aggregationTransformer
                )
            )
        );
    }
}