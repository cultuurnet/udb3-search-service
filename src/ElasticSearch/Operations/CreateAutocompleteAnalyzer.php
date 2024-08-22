<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use CultuurNet\UDB3\Search\FileReader;
use CultuurNet\UDB3\Search\Json;

final class CreateAutocompleteAnalyzer extends AbstractElasticSearchOperation
{
    public function run(): void
    {
        $this->client->indices()->putTemplate(
            [
                'name' => 'autocomplete_analyzer',
                'body' => Json::decodeAssociatively(
                    FileReader::read(__DIR__ . '/json/analyzer_autocomplete.json')
                ),
            ]
        );

        $this->logger->info('Autocomplete analyzer created.');
    }
}
