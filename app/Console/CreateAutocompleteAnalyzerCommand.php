<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Console;

use CultuurNet\UDB3\Search\ElasticSearch\Operations\CreateAutocompleteAnalyzer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class CreateAutocompleteAnalyzerCommand extends AbstractElasticSearchCommand
{
    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this
            ->setName('autocomplete-analyzer:create')
            ->setDescription('Creates or updates the template for the autocomplete analyzer.');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $operation = new CreateAutocompleteAnalyzer(
            $this->getElasticSearchClient(),
            $this->getLogger($output)
        );

        $operation->run();

        return 0;
    }
}
