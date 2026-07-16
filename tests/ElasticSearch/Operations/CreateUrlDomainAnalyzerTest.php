<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use Elasticsearch\Client;
use Psr\Log\LoggerInterface;

final class CreateUrlDomainAnalyzerTest extends AbstractOperationTestCase
{
    protected function createOperation(Client $client, LoggerInterface $logger): CreateUrlDomainAnalyzer
    {
        return new CreateUrlDomainAnalyzer($client, $logger);
    }

    /**
     * @test
     */
    public function it_puts_a_new_or_updated_index_template_for_a_url_domain_analyzer(): void
    {
        $this->indices->expects($this->once())
            ->method('putTemplate')
            ->with(
                [
                    'name' => 'url_domain_analyzer',
                    'body' => [
                        'template' => '*',
                        'settings' => [
                            'analysis' => [
                                'filter' => [
                                    'www_filter' => [
                                        'type' => 'pattern_replace',
                                        'pattern' => '^www\\.',
                                        'replacement' => '',
                                    ],
                                ],
                                'analyzer' => [
                                    'url_domain_analyzer' => [
                                        'tokenizer' => 'keyword',
                                        'filter' => ['lowercase', 'www_filter'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            );

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Url domain analyzer created.');

        $this->operation->run();
    }
}
