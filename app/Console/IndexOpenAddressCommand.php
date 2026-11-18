<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Console;

use CultuurNet\UDB3\Search\ElasticSearch\Client\ElasticSearchClient;
use CultuurNet\UDB3\Search\ElasticSearch\Operations\ReplaceLocationSuggestionsIndex;
use CultuurNet\UDB3\Search\LocationSuggestions\OpenAddressCsvReader;
use CultuurNet\UDB3\Search\LocationSuggestions\OpenAddressDocuments;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class IndexOpenAddressCommand extends AbstractElasticSearchCommand
{
    private string $indexPrefix;

    private string $schemaVersion;

    private string $alias;

    private int $numberOfShards;

    private int $numberOfReplicas;

    public function __construct(
        ElasticSearchClient $client,
        string $indexPrefix,
        string $schemaVersion,
        string $alias,
        int $numberOfShards,
        int $numberOfReplicas
    ) {
        parent::__construct($client);
        $this->indexPrefix = $indexPrefix;
        $this->schemaVersion = $schemaVersion;
        $this->alias = $alias;
        $this->numberOfShards = $numberOfShards;
        $this->numberOfReplicas = $numberOfReplicas;
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
            OpenAddressDocuments::fromRows(self::rows($files)),
            $this->numberOfShards,
            $this->numberOfReplicas
        );

        $logger->info(sprintf('Peak memory: %d MB.', memory_get_peak_usage(true) / 1024 / 1024));

        return 0;
    }

    /**
     * @param list<string> $files
     * @return \Generator<array<string, string>>
     */
    private static function rows(array $files): \Generator
    {
        foreach ($files as $file) {
            yield from OpenAddressCsvReader::rows($file);
        }
    }
}
