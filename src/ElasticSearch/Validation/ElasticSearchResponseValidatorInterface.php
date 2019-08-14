<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\Validation;

interface ElasticSearchResponseValidatorInterface
{
    /**
     * @param array $responseData
     *   Decoded ElasticSearch JSON response body.
     *
     * @throws InvalidElasticSearchResponseException
     */
    public function validate(array $responseData);
}
