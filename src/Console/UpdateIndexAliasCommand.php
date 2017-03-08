<?php

namespace CultuurNet\UDB3\SearchService\Console;

use CultuurNet\UDB3\Search\ElasticSearch\Operations\UpdateIndexAlias;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateIndexAliasCommand extends AbstractElasticSearchCommand
{
    /**
     * @var string
     */
    private $aliasName;

    /**
     * @var string
     */
    private $newIndexName;

    /**
     * @param string $name
     * @param string $description
     * @param string $aliasName
     * @param string $newIndexName
     */
    public function __construct($name, $description, $aliasName, $newIndexName)
    {
        parent::__construct($name);
        $this->setDescription($description);
        $this->aliasName = $aliasName;
        $this->newIndexName = $newIndexName;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $operation = new UpdateIndexAlias(
            $this->getElasticSearchClient(),
            $this->getLogger($output)
        );

        $operation->run($this->aliasName, $this->newIndexName);
    }
}
