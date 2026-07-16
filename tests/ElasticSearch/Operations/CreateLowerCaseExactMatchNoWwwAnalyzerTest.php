<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use Elasticsearch\Client;
use Psr\Log\LoggerInterface;

final class CreateLowerCaseExactMatchNoWwwAnalyzerTest extends AbstractOperationTestCase
{
    protected function createOperation(Client $client, LoggerInterface $logger): CreateLowerCaseExactMatchNoWwwAnalyzer
    {
        return new CreateLowerCaseExactMatchNoWwwAnalyzer($client, $logger);
    }

    /**
     * @test
     */
    public function it_puts_a_new_or_updated_index_template_for_a_lowercase_exact_match_no_www_analyzer(): void
    {
        $this->indices->expects($this->once())
            ->method('putTemplate')
            ->with(
                [
                    'name' => 'lowercase_exact_match_no_www_analyzer',
                    'body' => [
                        'template' => '*',
                        'settings' => [
                            'analysis' => [
                                'filter' => [
                                    'remove_www_prefix_filter' => [
                                        'type' => 'pattern_replace',
                                        'pattern' => '^www\\.',
                                        'replacement' => '',
                                    ],
                                ],
                                'analyzer' => [
                                    'lowercase_exact_match_no_www_analyzer' => [
                                        'tokenizer' => 'keyword',
                                        'filter' => ['lowercase', 'remove_www_prefix_filter'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            );

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Lowercase exact match (no www) analyzer created.');

        $this->operation->run();
    }
}
