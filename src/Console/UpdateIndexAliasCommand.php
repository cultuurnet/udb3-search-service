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
     * @var string
     */
    private $oldIndexName;

    /**
     * @param string $name
     * @param string $description
     * @param string $aliasName
     * @param string $newIndexName
     * @param string|null $oldIndexName
     */
    public function __construct($name, $description, $aliasName, $newIndexName, $oldIndexName = null)
    {
        parent::__construct($name);
        $this->setDescription($description);
        $this->aliasName = $aliasName;
        $this->newIndexName = $newIndexName;
        $this->oldIndexName = $oldIndexName;
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

        $operation->run($this->aliasName, $this->newIndexName, $this->oldIndexName);
    }
}
