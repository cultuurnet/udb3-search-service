<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Console;

use CultuurNet\UDB3\Search\ElasticSearch\Client\ElasticSearchClient;
use CultuurNet\UDB3\Search\ElasticSearch\Operations\FillLocationSuggestionsPlacesIndex;
use CultuurNet\UDB3\Search\ElasticSearch\Operations\GetIndexNamesFromAlias;
use CultuurNet\UDB3\Search\LocationSuggestions\LocationSuggestionsIndices;
use CultuurNet\UDB3\Search\LocationSuggestions\MunicipalityRegions;
use CultuurNet\UDB3\Search\LocationSuggestions\PlaceDocuments;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class IndexPlacesCommand extends AbstractElasticSearchCommand
{
    public function __construct(
        ElasticSearchClient $client,
        private readonly string $schemaVersion,
        private readonly string $readAlias,
        private readonly string $placeReadIndex,
        private readonly int $numberOfShards,
        private readonly int $numberOfReplicas,
        private readonly MunicipalityRegions $municipalityRegions
    ) {
        parent::__construct($client);
    }

    protected function configure(): void
    {
        $this
            ->setName('location-suggestions:index-places')
            ->setDescription('Indexes every place of the search index as a location suggestion. Run it once; the live updates keep the places up to date after that.')
            ->addOption(
                'recreate',
                null,
                InputOption::VALUE_NONE,
                'Build a new places index instead of adding the missing places to the current one, for example after a change to how a place becomes a suggestion.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = $this->getLogger($output);
        $versionPrefix = LocationSuggestionsIndices::PLACES_PREFIX . $this->schemaVersion . '_';

        // A mapping change gives a new version, so the current index is only reused when it has this version.
        $currentIndexNames = array_values(array_filter(
            (new GetIndexNamesFromAlias($this->getElasticSearchClient(), $logger))->run(LocationSuggestionsIndices::PLACES_WRITE_ALIAS),
            fn (string $indexName): bool => str_starts_with($indexName, $versionPrefix)
        ));

        // The date keeps a new index apart from the live one, which stays searchable until the new one is filled.
        $indexName = $currentIndexNames === [] || $input->getOption('recreate')
            ? $versionPrefix . date('YmdHis')
            : $currentIndexNames[0];

        (new FillLocationSuggestionsPlacesIndex($this->getElasticSearchClient(), $logger))->run(
            $indexName,
            LocationSuggestionsIndices::PLACES_PREFIX . '*',
            $this->readAlias,
            LocationSuggestionsIndices::PLACES_WRITE_ALIAS,
            $this->placeReadIndex,
            new PlaceDocuments($this->municipalityRegions),
            $this->numberOfShards,
            $this->numberOfReplicas
        );

        return self::SUCCESS;
    }
}
