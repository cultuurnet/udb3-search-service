<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use CultuurNet\UDB3\Search\FileReader;
use CultuurNet\UDB3\Search\Json;

final class CreateLowerCaseExactMatchNoWwwAnalyzer extends AbstractElasticSearchOperation
{
    public function run(): void
    {
        $this->client->indices()->putTemplate(
            [
                'name' => 'lowercase_exact_match_no_www_analyzer',
                'body' => Json::decodeAssociatively(
                    FileReader::read(__DIR__ . '/json/analyzer_lowercase_exact_match_no_www.json')
                ),
            ]
        );

        $this->logger->info('Lowercase exact match (no www) analyzer created.');
    }
}
