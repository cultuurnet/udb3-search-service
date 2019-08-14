<?php

namespace CultuurNet\UDB3\Search\ElasticSearch;

use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Search\ElasticSearch\Aggregation\Aggregation;
use CultuurNet\UDB3\Search\ElasticSearch\Aggregation\AggregationTransformerInterface;
use CultuurNet\UDB3\Search\ElasticSearch\Validation\ElasticSearchResponseValidatorInterface;
use CultuurNet\UDB3\Search\ElasticSearch\Validation\PagedResultSetResponseValidator;
use CultuurNet\UDB3\Search\PagedResultSet;
use ValueObjects\Number\Natural;

class ElasticSearchPagedResultSetFactory implements ElasticSearchPagedResultSetFactoryInterface
{
    /**
     * @var AggregationTransformerInterface
     */
    private $aggregationTransformer;

    /**
     * @var ElasticSearchResponseValidatorInterface
     */
    private $responseValidator;

    /**
     * @param AggregationTransformerInterface $aggregationTransformer
     * @param ElasticSearchResponseValidatorInterface|null $responseValidator
     */
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
        array_walk(
            $aggregations,
            function (array &$aggregationData, $aggregationName) {
                $aggregationData = Aggregation::fromElasticSearchResponseAggregationData(
                    $aggregationName,
                    $aggregationData
                );
            }
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
                    $aggregations
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
