<?php

namespace CultuurNet\UDB3\SearchService\Console;

use CultuurNet\UDB3\Search\ElasticSearch\Operations\UpdateRegionMapping;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateRegionMappingCommand extends AbstractMappingCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('geoshapes:region-mapping')
            ->setDescription('Creates or updates the region mapping on the latest geoshapes index.');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $operation = new UpdateRegionMapping(
            $this->getElasticSearchClient(),
            $this->getLogger($output)
        );

        $operation->run($this->indexName, $this->documentType);
    }
}
