<?php

namespace CultuurNet\UDB3\SearchService\Console;

use CultuurNet\UDB3\Search\ElasticSearch\Operations\CreateIndex as CreateIndexOperation;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateIndexCommand extends AbstractElasticSearchCommand
{
    /**
     * @var string
     */
    private $indexName;

    /**
     * @param string $name
     * @param string $description
     * @param string $indexName
     */
    public function __construct($name, $description, $indexName)
    {
        parent::__construct($name);
        $this->setDescription($description);
        $this->indexName = $indexName;
    }

    public function configure()
    {
        $this
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
