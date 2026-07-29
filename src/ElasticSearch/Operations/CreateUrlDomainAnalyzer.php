<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use CultuurNet\UDB3\Search\FileReader;
use CultuurNet\UDB3\Search\Json;

final class CreateUrlDomainAnalyzer extends AbstractElasticSearchOperation
{
    public function run(): void
    {
        $this->client->indices()->putTemplate(
            [
                'name' => 'url_domain_analyzer',
                'body' => Json::decodeAssociatively(
                    FileReader::read(__DIR__ . '/json/analyzer_url_domain.json')
                ),
            ]
        );

        $this->logger->info('Url domain analyzer created.');
    }
}
