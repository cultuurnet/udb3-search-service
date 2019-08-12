<?php

namespace CultuurNet\UDB3\Search\ElasticSearch;

use PHPUnit_Framework_TestCase;

abstract class AbstractElasticSearchQueryBuilderTest extends PHPUnit_Framework_TestCase
{
    /**
     * @param $value
     * @return array
     */
    protected function expectedTextQuery($value, array $fields = [])
    {
        $textQuery = [
            'query' => $value,
            'default_operator' => 'AND',
        ];

        if (!empty($fields)) {
            $textQuery += ['fields' => $fields];
        }

        return $textQuery;
    }
}
