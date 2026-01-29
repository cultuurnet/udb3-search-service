<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Response\Elasticsearch;

final class GetIndexNamesFromAlias extends AbstractElasticSearchOperation
{
    /**
     * @param string $aliasName
     *   If an actual index name is given instead of an alias, the operation
     *   will return the same index name.
     *
     * @return string[]
     *   All index names the alias points to.
     */
    public function run(string $aliasName): array
    {
        try {
            $responseData = $this->client->indices()->get(['index' => $aliasName]);

            if (!$responseData instanceof Elasticsearch) {
                throw new \RuntimeException('Async response type from Elasticsearch client not supported');
            }

            return array_keys($responseData->asArray());
        } catch (ClientResponseException) {
            return [];
        }
    }
}
