<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Validation;

class PagedResultSetResponseValidator implements ElasticSearchResponseValidatorInterface
{
    /**
     * @throws InvalidElasticSearchResponseException
     */
    public function validate(array $responseData)
    {
        if (!isset($responseData['hits'])) {
            throw new InvalidElasticSearchResponseException(
                "ElasticSearch response does not contain a 'hits' property!"
            );
        }

        if (!isset($responseData['hits']['total'])) {
            throw new InvalidElasticSearchResponseException(
                "ElasticSearch response does not contain a 'hits.total' property!"
            );
        }

        if (!isset($responseData['hits']['hits'])) {
            throw new InvalidElasticSearchResponseException(
                "ElasticSearch response does not contain a 'hits.hits' property!"
            );
        }

        foreach ($responseData['hits']['hits'] as $key => $hit) {
            if (!isset($responseData['hits']['hits'][$key]['_id'])) {
                throw new InvalidElasticSearchResponseException(
                    "ElasticSearch response does not contain a 'hits.hits[{$key}]._id' property!"
                );
            }

            if (!isset($responseData['hits']['hits'][$key]['_source'])) {
                throw new InvalidElasticSearchResponseException(
                    "ElasticSearch response does not contain a 'hits.hits[{$key}]._source' property!"
                );
            }
        }
    }
}
