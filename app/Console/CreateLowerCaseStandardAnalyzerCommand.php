<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Console;

use CultuurNet\UDB3\Search\ElasticSearch\Operations\CreateLowerCaseStandardAnalyzer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class CreateLowerCaseStandardAnalyzerCommand extends AbstractElasticSearchCommand
{
    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this
            ->setName('lowercase-standard-analyzer:create')
            ->setDescription('Creates or updates the template for a lowercase & standard analyzer.');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $operation = new CreateLowerCaseStandardAnalyzer(
            $this->getElasticSearchClient(),
            $this->getLogger($output)
        );

        $operation->run();

        return 0;
    }
}
