<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Console;

use CultuurNet\UDB3\Search\ElasticSearch\Operations\CreateIndex as CreateIndexOperation;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class CreateIndexCommand extends AbstractElasticSearchCommand
{
    /**
     * @inheritdoc
     */
    public function configure()
    {
        $this
            ->setName('index:create')
            ->setDescription('Creates an empty index.')
            ->addArgument(
                'target',
                InputArgument::REQUIRED,
                'Name of the index to create.'
            )
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Delete index first if it already exists. (New index will be empty!)'
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $name = $input->getArgument('target');
        $force = (bool) $input->getOption('force');

        $operation = new CreateIndexOperation(
            $this->getElasticSearchClient(),
            $this->getLogger($output)
        );

        $operation->run($name, $force);

        return 0;
    }
}
