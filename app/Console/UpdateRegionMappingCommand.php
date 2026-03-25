<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Console;

use CultuurNet\UDB3\Search\ElasticSearch\Operations\UpdateRegionMapping;
use Elasticsearch\Client;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class UpdateRegionMappingCommand extends AbstractMappingCommand
{
    public function __construct(Client $client, string $indexName, string $documentType)
    {
        parent::__construct($client, $indexName, $documentType);
    }

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

        if ($this->typeEnabled) {
            $operation->enableType();
        }

        $operation->run($this->indexName, $this->documentType);

        return 0;
    }
}
