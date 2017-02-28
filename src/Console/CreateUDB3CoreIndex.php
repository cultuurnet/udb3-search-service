<?php

namespace CultuurNet\UDB3\SearchService\Console;

use CultuurNet\UDB3\Search\ElasticSearch\Operations\CreateIndex;
use CultuurNet\UDB3\Search\ElasticSearch\Operations\IndexNames;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateUDB3CoreIndex extends AbstractElasticSearchCommand
{
    public function configure()
    {
        $this
            ->setName('udb3-core:create')
            ->setDescription('Create the latest udb3 core index.')
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Delete index first if it already exists. (New index will be empty!)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $force = (bool) $input->getOption('force');

        $operation = new CreateIndex(
            $this->getElasticSearchClient(),
            $this->getLogger($output)
        );

        $operation->run(IndexNames::UDB3_CORE_LATEST, $force);
    }
}
