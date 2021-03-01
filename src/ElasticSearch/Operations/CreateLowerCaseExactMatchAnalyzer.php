<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

class CreateLowerCaseExactMatchAnalyzer extends AbstractElasticSearchOperation
{
    public function run()
    {
        $this->client->indices()->putTemplate(
            [
                'name' => 'lowercase_exact_match_analyzer',
                'body' => json_decode(
                    file_get_contents(__DIR__ . '/json/analyzer_lowercase_exact_match.json'),
                    true
                ),
            ]
        );

        $this->logger->info('Lowercase exact match analyzer created.');
    }
}
