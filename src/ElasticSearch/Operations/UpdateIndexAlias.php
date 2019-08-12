<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

class UpdateIndexAlias extends AbstractElasticSearchOperation
{
    /**
     * @param string $aliasName
     * @param string $newIndexName
     */
    public function run(
        $aliasName,
        $newIndexName
    ) {
        $getAliasParams = [
            'name' => $aliasName
        ];

        $aliasOnNewIndex = [
            'index' => $newIndexName,
            'name' => $aliasName,
        ];

        // To avoid an exception from getAlias first check if the alias exist with existsAlias.
        if ($this->client->indices()->existsAlias($getAliasParams)) {
            $aliases = $this->client->indices()->getAlias($getAliasParams);

            foreach ($aliases as $key => $index) {
                $deleteAlias = [
                    'index' => $key,
                    'name' => $aliasName
                ];
                $this->client->indices()->deleteAlias($deleteAlias);
                $this->logger->info("Deleted alias {$deleteAlias['name']} from index {$deleteAlias['index']}.");
            }
        }

        $this->client->indices()->putAlias($aliasOnNewIndex);
        $this->logger->info("Created alias {$aliasName} on index {$newIndexName}.");
    }
}
