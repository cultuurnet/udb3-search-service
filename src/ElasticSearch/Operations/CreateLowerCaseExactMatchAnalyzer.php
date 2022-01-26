<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use CultuurNet\UDB3\Search\Json;

final class CreateLowerCaseExactMatchAnalyzer extends AbstractElasticSearchOperation
{
    public function run()
    {
        $this->client->indices()->putTemplate(
            [
                'name' => 'lowercase_exact_match_analyzer',
                'body' => Json::decodeAssociatively(
                    file_get_contents(__DIR__ . '/json/analyzer_lowercase_exact_match.json')
                ),
            ]
        );

        $this->logger->info('Lowercase exact match analyzer created.');
    }
}
