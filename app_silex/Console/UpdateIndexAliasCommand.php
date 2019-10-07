<?php

namespace CultuurNet\UDB3\SearchService\Console;

use CultuurNet\UDB3\Search\ElasticSearch\Operations\UpdateIndexAlias;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateIndexAliasCommand extends AbstractElasticSearchCommand
{
    /**
     * @inheritdoc
     */
    public function configure()
    {
        $this
            ->setName('index:update-alias')
            ->setDescription('Moves an alias to the given index.')
            ->addArgument(
                'alias',
                InputArgument::REQUIRED,
                'Name of the alias to move.'
            )
            ->addArgument(
                'target',
                InputArgument::REQUIRED,
                'Name of the index to move the alias to.'
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $aliasName = $input->getArgument('alias');
        $indexName = $input->getArgument('target');

        $operation = new UpdateIndexAlias(
            $this->getElasticSearchClient(),
            $this->getLogger($output)
        );

        $operation->run($aliasName, $indexName);
    }
}
