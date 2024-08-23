<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use Elasticsearch\Client;
use Psr\Log\LoggerInterface;

final class CreateAutocompleteAnalyzerTest extends AbstractOperationTestCase
{
    protected function createOperation(Client $client, LoggerInterface $logger): CreateAutocompleteAnalyzer
    {
        return new CreateAutocompleteAnalyzer($client, $logger);
    }

    /**
     * @test
     */
    public function it_puts_a_new_or_updated_index_template_for_an_autocomplete_analyzer(): void
    {
        $this->indices->expects($this->once())
            ->method('putTemplate')
            ->with(
                [
                    'name' => 'autocomplete_analyzer',
                    'body' => [
                        'index_patterns' => '*',
                        'settings' => [
                            'analysis' => [
                                'filter' => [
                                    'autocomplete_filter' => [
                                        'type' => 'edge_ngram',
                                        'min_gram' => 1,
                                        'max_gram' => 20,
                                    ],
                                ],
                                'analyzer' => [
                                    'autocomplete_analyzer' => [
                                        'type' => 'custom',
                                        'tokenizer' => 'standard',
                                        'filter' => [
                                            'lowercase',
                                            'autocomplete_filter',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            );

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Autocomplete analyzer created.');

        $this->operation->run();
    }
}
