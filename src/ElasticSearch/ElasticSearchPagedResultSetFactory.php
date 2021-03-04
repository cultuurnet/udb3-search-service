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
use ValueObjects\Number\Natural;

final class ElasticSearchPagedResultSetFactory implements ElasticSearchPagedResultSetFactoryInterface
{
    /**
     * @var AggregationTransformerInterface
     */
    private $aggregationTransformer;

    /**
     * @var ElasticSearchResponseValidatorInterface
     */
    private $responseValidator;


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

    /**
     * @inheritdoc
     */
    public function createPagedResultSet(Natural $perPage, array $response)
    {
        $this->responseValidator->validate($response);

        $total = new Natural($response['hits']['total']);

        $results = array_map(
            function (array $result) {
                return (new JsonDocument($result['_id']))
                    ->withBody($result['_source']);
            },
            $response['hits']['hits']
        );

        $aggregations = isset($response['aggregations']) ? $response['aggregations'] : [];

        if (isset($aggregations['total'])) {
            $total = new Natural($aggregations['total']['value']);
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
