<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

final class GetAliases extends AbstractElasticSearchOperation
{
    /**
     * @return array<string, string>
     *   Index name keyed by alias name, e.g. ['udb3_core_read' => 'udb3_core_v20260714120000'].
     */
    public function run(): array
    {
        $indexNamesByAlias = [];

        foreach ($this->client->indices()->getAlias([]) as $indexName => $indexData) {
            foreach (array_keys($indexData['aliases'] ?? []) as $aliasName) {
                $indexNamesByAlias[(string) $aliasName] = (string) $indexName;
            }
        }

        ksort($indexNamesByAlias);

        return $indexNamesByAlias;
    }
}
