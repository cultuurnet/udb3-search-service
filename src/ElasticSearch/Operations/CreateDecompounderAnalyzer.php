<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use CultuurNet\UDB3\Search\FileReader;
use CultuurNet\UDB3\Search\Json;

final class CreateDecompounderAnalyzer extends AbstractElasticSearchOperation
{
    public function run(): void
    {
        $this->client->indices()->putTemplate(
            [
                'name' => 'decompounder_analyzer',
                'body' => Json::decodeAssociatively(
                    FileReader::read(__DIR__ . '/json/analyzer_decompounder.json')
                ),
            ]
        );

        $this->logger->info('Hyphenation decompounder analyzer created.');
    }
}
