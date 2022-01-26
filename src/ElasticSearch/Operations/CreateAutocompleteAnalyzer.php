<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use CultuurNet\UDB3\Search\Json;

final class CreateAutocompleteAnalyzer extends AbstractElasticSearchOperation
{
    public function run()
    {
        $this->client->indices()->putTemplate(
            [
                'name' => 'autocomplete_analyzer',
                'body' => Json::decodeAssociatively(
                    file_get_contents(__DIR__ . '/json/analyzer_autocomplete.json')
                ),
            ]
        );

        $this->logger->info('Autocomplete analyzer created.');
    }
}
