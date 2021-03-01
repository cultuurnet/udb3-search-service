<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Console;

use CultuurNet\UDB3\Search\ElasticSearch\Operations\UpdateOrganizerMapping;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateOrganizerMappingCommand extends AbstractMappingCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('udb3-core:organizer-mapping')
            ->setDescription('Creates or updates the organizer mapping on the latest udb3_core index.');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $operation = new UpdateOrganizerMapping(
            $this->getElasticSearchClient(),
            $this->getLogger($output)
        );

        $operation->run($this->indexName, $this->documentType);

        return 0;
    }
}
