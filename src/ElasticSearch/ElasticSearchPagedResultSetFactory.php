<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch;

use CultuurNet\UDB3\Search\ElasticSearch\Aggregation\Aggregation;
use CultuurNet\UDB3\Search\ElasticSearch\Aggregation\AggregationTransformerInterface;
use CultuurNet\UDB3\Search\ElasticSearch\Validation\ElasticSearchResponseValidatorInterface;
use CultuurNet\UDB3\Search\ElasticSearch\Validation\PagedResultSetResponseValidator;
use CultuurNet\UDB3\Search\PagedResultSet;
use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use InvalidArgumentException;

final class ElasticSearchPagedResultSetFactory implements ElasticSearchPagedResultSetFactoryInterface
{
    private AggregationTransformerInterface $aggregationTransformer;

    private ?ElasticSearchResponseValidatorInterface $responseValidator;


    public function __construct(
        AggregationTransformerInterface $aggregationTransformer,
        ElasticSearchResponseValidatorInterface $responseValidator = null
    ) {
        if (is_null($responseValidator)) {
            $responseValidator = new PagedResultSetResponseValidator();
        }

        $this->aggregationTransformer = $aggregationTransformer;
        $this->responseValidator = $responseValidator;
    }

    public function createPagedResultSet(int $perPage, array $response): PagedResultSet
    {
        $this->responseValidator->validate($response);

        $total = $response['hits']['total']['value'];

        $results = array_map(
            fn (array $result): JsonDocument => (new JsonDocument($result['_id']))
                ->withBody($result['_source']),
            $response['hits']['hits']
        );

        $aggregations = $response['aggregations'] ?? [];

        if (isset($aggregations['total'])) {
            $total = $aggregations['total']['value'];
        }

        $bucketAggregations = array_filter(
            array_map(
                function (array $aggregationData, string $aggregationName): ?Aggregation {
                    try {
                        return Aggregation::fromElasticSearchResponseAggregationData(
                            $aggregationName,
                            $aggregationData
                        );
                    } catch (InvalidArgumentException $e) {
                        // If the aggregation has no buckets it will result in an InvalidArgumentException, and it's not
                        // an aggregation used for facets.
                        return null;
                    }
                },
                $aggregations,
                array_keys($aggregations)
            )
        );

        $facets = array_values(
            array_filter(
                array_map(
                    function ($aggregation) {
                        if (!$this->aggregationTransformer->supports($aggregation)) {
                            return null;
                        }
                        return $this->aggregationTransformer->toFacetTree($aggregation);
                    },
                    $bucketAggregations
                )
            )
        );

        $pagedResultSet = (new PagedResultSet(
            $total,
            $perPage,
            $results
        ))->withFacets(...$facets);

        return $pagedResultSet;
    }
}
