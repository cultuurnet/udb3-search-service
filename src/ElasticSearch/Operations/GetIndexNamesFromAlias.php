<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use Elasticsearch\Common\Exceptions\Missing404Exception;

class GetIndexNamesFromAlias extends AbstractElasticSearchOperation
{
    /**
     * @param string $aliasName
     *   If an actual index name is given instead of an alias, the operation
     *   will return the same index name.
     *
     * @return string[]
     *   All index names the alias points to.
     */
    public function run($aliasName)
    {
        try {
            /* @var array $responseData */
            $responseData = $this->client->indices()->get(['index' => $aliasName]);
            return array_keys($responseData);
        } catch (Missing404Exception $e) {
            return [];
        }
    }
}
