<?php

namespace CultuurNet\UDB3\SearchService\Console;

use CultuurNet\UDB3\Search\ElasticSearch\Operations\CreateIndex as CreateIndexOperation;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateIndex extends AbstractElasticSearchCommand
{
    /**
     * @var string
     */
    private $desc;

    /**
     * @var string
     */
    private $indexName;

    public function __construct($name, $desc, $indexName)
    {
        parent::__construct($name);
        $this->desc = $desc;
        $this->indexName = $indexName;
    }

    public function configure()
    {
        $this
            ->setDescription($this->desc)
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

        $operation = new CreateIndexOperation(
            $this->getElasticSearchClient(),
            $this->getLogger($output)
        );

        $operation->run($this->indexName, $force);
    }
}
