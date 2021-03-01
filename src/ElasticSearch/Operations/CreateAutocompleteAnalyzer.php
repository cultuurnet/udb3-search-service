<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

class CreateAutocompleteAnalyzer extends AbstractElasticSearchOperation
{
    public function run()
    {
        $this->client->indices()->putTemplate(
            [
                'name' => 'autocomplete_analyzer',
                'body' => json_decode(
                    file_get_contents(__DIR__ . '/json/analyzer_autocomplete.json'),
                    true
                ),
            ]
        );

        $this->logger->info('Autocomplete analyzer created.');
    }
}
