<?php

namespace CultuurNet\UDB3\Search\ElasticSearch;

use PHPUnit\Framework\TestCase;

abstract class AbstractElasticSearchQueryBuilderTest extends TestCase
{
    protected function expectedTextQuery(string $value, array $fields = []): array
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
