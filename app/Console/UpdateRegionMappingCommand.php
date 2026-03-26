<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Console;

use CultuurNet\UDB3\Search\ElasticSearch\Operations\UpdateRegionMapping;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class UpdateRegionMappingCommand extends AbstractMappingCommand
{
    protected function configure(): void
    {
        $this
            ->setName('geoshapes:region-mapping')
            ->setDescription('Creates or updates the region mapping on the latest geoshapes index.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $operation = new UpdateRegionMapping(
            $this->getElasticSearchClient(),
            $this->getLogger($output)
        );

        if ($this->usesCompatibilityMode()) {
            $operation->enableElasticSearch5CompatibilityMode();
        }

        $operation->run($this->indexName, $this->documentType);

        return 0;
    }
}
