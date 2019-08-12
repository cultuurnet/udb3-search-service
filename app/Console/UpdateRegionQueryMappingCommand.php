<?php

namespace CultuurNet\UDB3\SearchService\Console;

use CultuurNet\UDB3\Search\ElasticSearch\Operations\UpdateRegionQueryMapping;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateRegionQueryMappingCommand extends AbstractMappingCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('udb3-core:region-query-mapping')
            ->setDescription('Creates or updates the region_query mapping on the latest udb3_core index.');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $operation = new UpdateRegionQueryMapping(
            $this->getElasticSearchClient(),
            $this->getLogger($output)
        );

        $operation->run($this->indexName, $this->documentType);
    }
}
