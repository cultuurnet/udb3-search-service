<?php

namespace CultuurNet\UDB3\SearchService\Console;

use CultuurNet\UDB3\Search\ElasticSearch\Operations\DeleteIndex;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteIndexCommand extends AbstractElasticSearchCommand
{
    /**
     * @inheritdoc
     */
    public function configure()
    {
        $this
            ->setName('index:delete')
            ->setDescription('Deletes an index.')
            ->addArgument(
                'target',
                InputArgument::REQUIRED,
                'Name of the index to delete.'
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $target = $input->getArgument('target');

        $operation = new DeleteIndex(
            $this->getElasticSearchClient(),
            $this->getLogger($output)
        );

        $operation->run($target);
    }
}
