<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use Elasticsearch\Client;
use Psr\Log\LoggerInterface;

final class CreateLowerCaseExactMatchAnalyzerTest extends AbstractOperationTestCase
{
    /**
     * @return CreateLowerCaseExactMatchAnalyzer
     */
    protected function createOperation(Client $client, LoggerInterface $logger)
    {
        return new CreateLowerCaseExactMatchAnalyzer($client, $logger);
    }

    /**
     * @test
     */
    public function it_puts_a_new_or_updated_index_template_for_a_lowercase_exact_match_analyzer()
    {
        $this->indices->expects($this->once())
            ->method('putTemplate')
            ->with(
                [
                    'name' => 'lowercase_exact_match_analyzer',
                    'body' => [
                        'template' => '*',
                        'settings' => [
                            'analysis' => [
                                'analyzer' => [
                                    'lowercase_exact_match_analyzer' => [
                                        'tokenizer' => 'keyword',
                                        'filter' => ['lowercase'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            );

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Lowercase exact match analyzer created.');

        $this->operation->run();
    }
}
