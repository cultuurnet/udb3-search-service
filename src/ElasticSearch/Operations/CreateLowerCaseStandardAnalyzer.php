<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use CultuurNet\UDB3\Search\FileReader;
use CultuurNet\UDB3\Search\Json;

final class CreateLowerCaseStandardAnalyzer extends AbstractElasticSearchOperation
{
    public function run(): void
    {
        $this->client->indices()->putTemplate(
            [
                'name' => 'lowercase_standard_analyzer',
                'body' => Json::decodeAssociatively(
                    FileReader::read(__DIR__ . '/json/analyzer_lowercase_standard.json')
                ),
            ]
        );

        $this->logger->info('Lowercase standard analyzer created.');
    }
}
