<?php

namespace CultuurNet\UDB3\SearchService\Console;

use CultuurNet\UDB3\Search\ElasticSearch\Operations\CheckIndexExists;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckIndexExistsCommand extends AbstractElasticSearchCommand
{
    /**
     * @inheritdoc
     */
    public function configure()
    {
        $this
            ->setName('index:exists')
            ->setDescription('Checks if an exists or not.')
            ->addArgument(
                'target',
                InputArgument::REQUIRED,
                'Name of the index to check.'
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $target = $input->getArgument('target');

        $operation = new CheckIndexExists(
            $this->getElasticSearchClient(),
            $this->getLogger($output)
        );

        $exists = $operation->run($target);

        return $exists ? 0 : 1;
    }
}
