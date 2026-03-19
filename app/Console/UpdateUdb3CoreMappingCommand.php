<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Console;

use CultuurNet\UDB3\Search\ElasticSearch\Operations\UpdateUdb3CoreMapping;
use Elasticsearch\Client;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class UpdateUdb3CoreMappingCommand extends AbstractMappingCommand
{
    private int $elasticsearchVersion;

    public function __construct(Client $client, string $indexName, string $documentType, int $elasticsearchVersion = 5)
    {
        parent::__construct($client, $indexName, $documentType);
        $this->elasticsearchVersion = $elasticsearchVersion;
    }

    protected function configure(): void
    {
        $this
            ->setName('udb3-core:update-mapping')
            ->setDescription('Creates or updates the udb3 core mapping on the latest udb3_core index.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $operation = new UpdateUdb3CoreMapping(
            $this->getElasticSearchClient(),
            $this->getLogger($output),
            $this->elasticsearchVersion
        );

        $operation->run($this->indexName, $this->documentType);

        return 0;
    }
}
