<?php

namespace CultuurNet\UDB3\SearchService\Console;

use CultuurNet\UDB3\Search\ElasticSearch\Operations\CreateLowerCaseAnalyzer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateLowerCaseAnalyzerCommand extends AbstractElasticSearchCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('lowercase-analyzer:create')
            ->setDescription('Creates or updates the template for a lowercase analyzer.');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $operation = new CreateLowerCaseAnalyzer(
            $this->getElasticSearchClient(),
            $this->getLogger($output)
        );

        $operation->run();
    }
}
