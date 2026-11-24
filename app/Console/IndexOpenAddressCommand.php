<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Console;

use CultuurNet\UDB3\Search\ElasticSearch\Client\ElasticSearchClient;
use CultuurNet\UDB3\Search\ElasticSearch\Operations\ReplaceLocationSuggestionsIndex;
use CultuurNet\UDB3\Search\LocationSuggestions\MunicipalityRegions;
use CultuurNet\UDB3\Search\LocationSuggestions\OpenAddressCsvReader;
use CultuurNet\UDB3\Search\LocationSuggestions\OpenAddressDocuments;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class IndexOpenAddressCommand extends AbstractElasticSearchCommand
{
    public function __construct(
        ElasticSearchClient $client,
        private readonly string $indexPrefix,
        private readonly string $schemaVersion,
        private readonly string $alias,
        private readonly int $numberOfShards,
        private readonly int $numberOfReplicas,
        private readonly MunicipalityRegions $municipalityRegions
    ) {
        parent::__construct($client);
    }

    protected function configure(): void
    {
        $this
            ->setName('location-suggestions:index-openaddress')
            ->setDescription('Indexes the BeST OpenAddress CSV files into a new location suggestions index and moves the alias to it.')
            ->addArgument(
                'files',
                InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                'The OpenAddress CSV files, for example openaddress-bevlg.csv openaddress-bewal.csv openaddress-bebru.csv'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var list<string> $files */
        $files = $input->getArgument('files');

        // The date keeps a re-index with an unchanged mapping apart from the live index.
        $indexName = $this->indexPrefix . $this->schemaVersion . '_' . date('YmdHis');

        $logger = $this->getLogger($output);
        $operation = new ReplaceLocationSuggestionsIndex($this->getElasticSearchClient(), $logger);
        $operation->run(
            $indexName,
            $this->indexPrefix . '*',
            $this->alias,
            new OpenAddressDocuments(new OpenAddressCsvReader($files), $this->municipalityRegions, $logger),
            $this->numberOfShards,
            $this->numberOfReplicas
        );

        // The rows are streamed, so memory should stay flat however large the CSVs get.
        // Logging the peak shows when a change starts loading everything at once.
        $logger->info(sprintf('Peak memory: %d MB.', memory_get_peak_usage(true) / 1024 / 1024));

        return self::SUCCESS;
    }
}
