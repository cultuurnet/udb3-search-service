<?php

namespace CultuurNet\UDB3\SearchService\Console;

use CultuurNet\UDB3\Search\ElasticSearch\Operations\TestIndexExists;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class TestIndexExistsCommand extends AbstractElasticSearchCommand
{
    /**
     * @var string|null
     */
    private $indexName;

    /**
     * @param string $name
     * @param string $description
     * @param string $indexName
     */
    public function __construct($name, $description, $indexName = null)
    {
        parent::__construct($name);
        $this->setDescription($description);
        $this->indexName = $indexName;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (is_null($this->indexName)) {
            $logger = new ConsoleLogger($output);
            $logger->info('No previous index available.');
            return 1;
        }

        $operation = new TestIndexExists(
            $this->getElasticSearchClient(),
            $this->getLogger($output)
        );

        $exists = $operation->run($this->indexName);

        return $exists ? 0 : 1;
    }
}
