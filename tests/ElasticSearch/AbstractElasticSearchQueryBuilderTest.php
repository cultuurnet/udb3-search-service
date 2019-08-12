<?php

namespace CultuurNet\UDB3\Search\ElasticSearch;

use PHPUnit\Framework\TestCase;

abstract class AbstractElasticSearchQueryBuilderTest extends TestCase
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
