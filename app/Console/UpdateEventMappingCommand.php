<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Console;

use CultuurNet\UDB3\Search\ElasticSearch\Operations\UpdateEventMapping;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class UpdateEventMappingCommand extends AbstractMappingCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('udb3-core:event-mapping')
            ->setDescription('Creates or updates the event mapping on the latest udb3_core index.');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $operation = new UpdateEventMapping(
            $this->getElasticSearchClient(),
            $this->getLogger($output)
        );

        $operation->run($this->indexName, $this->documentType);

        return 0;
    }
}
