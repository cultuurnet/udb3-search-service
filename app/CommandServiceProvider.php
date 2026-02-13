<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService;

use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\Search\ElasticSearch\Operations\SchemaVersions;
use CultuurNet\UDB3\SearchService\Console\CheckIndexExistsCommand;
use CultuurNet\UDB3\SearchService\Console\ConsumeCommand;
use CultuurNet\UDB3\SearchService\Console\CreateAutocompleteAnalyzerCommand;
use CultuurNet\UDB3\SearchService\Console\CreateDecompounderAnalyzerCommand;
use CultuurNet\UDB3\SearchService\Console\CreateIndexCommand;
use CultuurNet\UDB3\SearchService\Console\CreateLowerCaseExactMatchAnalyzerCommand;
use CultuurNet\UDB3\SearchService\Console\CreateLowerCaseStandardAnalyzerCommand;
use CultuurNet\UDB3\SearchService\Console\DeleteIndexCommand;
use CultuurNet\UDB3\SearchService\Console\IndexRegionsCommand;
use CultuurNet\UDB3\SearchService\Console\InstallGeoShapesCommand;
use CultuurNet\UDB3\SearchService\Console\InstallUDB3CoreCommand;
use CultuurNet\UDB3\SearchService\Console\MigrateElasticSearchCommand;
use CultuurNet\UDB3\SearchService\Console\ReindexPermanentOffersCommand;
use CultuurNet\UDB3\SearchService\Console\ReindexUDB3CoreCommand;
use CultuurNet\UDB3\SearchService\Console\UpdateEventMappingCommand;
use CultuurNet\UDB3\SearchService\Console\UpdateIndexAliasCommand;
use CultuurNet\UDB3\SearchService\Console\UpdateOrganizerMappingCommand;
use CultuurNet\UDB3\SearchService\Console\UpdatePlaceMappingCommand;
use CultuurNet\UDB3\SearchService\Console\UpdateRegionMappingCommand;
use Elasticsearch\Client;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;
use Symfony\Component\Finder\Finder;

final class CommandServiceProvider extends BaseServiceProvider
{
    protected $provides = [
        Application::class,
    ];

    public function register(): void
    {
        $this->add(
            Application::class,
            function (): Application {
                $commandMap = [
                    'elasticsearch:migrate' => MigrateElasticSearchCommand::class,
                    'lowercase-exact-match-analyzer:create' => CreateLowerCaseExactMatchAnalyzerCommand::class,
                    'lowercase-standard-analyzer:create' => CreateLowerCaseStandardAnalyzerCommand::class,
                    'autocomplete-analyzer:create' => CreateAutocompleteAnalyzerCommand::class,
                    'decompounder-analyzer:create' => CreateDecompounderAnalyzerCommand::class,
                    'index:exists' => CheckIndexExistsCommand::class,
                    'index:create' => CreateIndexCommand::class,
                    'index:delete' => DeleteIndexCommand::class,
                    'index:update-alias' => UpdateIndexAliasCommand::class,
                    'udb3-core:organizer-mapping' => UpdateOrganizerMappingCommand::class,
                    'udb3-core:event-mapping' => UpdateEventMappingCommand::class,
                    'udb3-core:place-mapping' => UpdatePlaceMappingCommand::class,
                    'udb3-core:reindex' => ReindexUDB3CoreCommand::class,
                    'udb3-core:reindex-permanent' => ReindexPermanentOffersCommand::class,
                    'udb3-core:install' => InstallUDB3CoreCommand::class,
                    'geoshapes:region-mapping' => UpdateRegionMappingCommand::class,
                    'geoshapes:index-regions' => IndexRegionsCommand::class,
                    'geoshapes:install' => InstallGeoShapesCommand::class,
                ];

                foreach (AmqpProvider::getConsumers($this) as $consumerKey => $consumerConfig) {
                    $commandName = $this->getAmqpConsumerCommandName($consumerKey);
                    $commandMap[$commandName] = $commandName;
                }

                $application = new Application('udb3-search');

                $application->setCommandLoader(
                    new ContainerCommandLoader(
                        $this->getContainer(),
                        $commandMap
                    )
                );

                return $application;
            }
        );

        $this->add(
            UpdateOrganizerMappingCommand::class,
            fn (): UpdateOrganizerMappingCommand => new UpdateOrganizerMappingCommand(
                $this->get(Client::class),
                $this->parameter('elasticsearch.udb3_core_index.prefix') . SchemaVersions::UDB3_CORE,
                $this->parameter('elasticsearch.organizer.document_type')
            )
        );

        $this->add(
            UpdateEventMappingCommand::class,
            fn (): UpdateEventMappingCommand => new UpdateEventMappingCommand(
                $this->get(Client::class),
                $this->parameter('elasticsearch.udb3_core_index.prefix') . SchemaVersions::UDB3_CORE,
                $this->parameter('elasticsearch.event.document_type')
            )
        );

        $this->add(
            UpdatePlaceMappingCommand::class,
            fn (): UpdatePlaceMappingCommand => new UpdatePlaceMappingCommand(
                $this->get(Client::class),
                $this->parameter('elasticsearch.udb3_core_index.prefix') . SchemaVersions::UDB3_CORE,
                $this->parameter('elasticsearch.place.document_type')
            )
        );

        $this->add(
            ReindexUDB3CoreCommand::class,
            fn (): ReindexUDB3CoreCommand => new ReindexUDB3CoreCommand(
                $this->get(Client::class),
                $this->parameter('elasticsearch.udb3_core_index.reindexation.from'),
                $this->get(EventBus::class),
                $this->get('elasticsearch_indexation_strategy'),
                $this->parameter('elasticsearch.udb3_core_index.reindexation.scroll_ttl'),
                $this->parameter('elasticsearch.udb3_core_index.reindexation.scroll_size'),
                $this->parameter('elasticsearch.udb3_core_index.reindexation.bulk_threshold')
            )
        );

        $this->add(
            ReindexPermanentOffersCommand::class,
            fn (): ReindexPermanentOffersCommand => new ReindexPermanentOffersCommand(
                $this->get(Client::class),
                $this->parameter('elasticsearch.udb3_core_index.reindexation.from'),
                $this->get(EventBus::class),
                $this->get('elasticsearch_indexation_strategy'),
                $this->parameter('elasticsearch.udb3_core_index.reindexation.scroll_ttl'),
                $this->parameter('elasticsearch.udb3_core_index.reindexation.scroll_size'),
                $this->parameter('elasticsearch.udb3_core_index.reindexation.bulk_threshold')
            )
        );

        $this->add(
            InstallUDB3CoreCommand::class,
            fn (): InstallUDB3CoreCommand => new InstallUDB3CoreCommand(
                $this->get(Client::class),
                $this->parameter('elasticsearch.udb3_core_index.prefix') . SchemaVersions::UDB3_CORE,
                $this->parameter('elasticsearch.udb3_core_index.write_alias'),
                $this->parameter('elasticsearch.udb3_core_index.read_alias')
            )
        );

        $this->add(
            UpdateRegionMappingCommand::class,
            fn (): UpdateRegionMappingCommand => new UpdateRegionMappingCommand(
                $this->get(Client::class),
                $this->parameter('elasticsearch.geoshapes_index.prefix') . SchemaVersions::GEOSHAPES,
                $this->parameter('elasticsearch.region.document_type')
            )
        );

        $this->add(
            IndexRegionsCommand::class,
            fn (): IndexRegionsCommand => new IndexRegionsCommand(
                $this->get(Client::class),
                $this->get(Finder::class),
                $this->parameter('elasticsearch.geoshapes_index.indexation.to'),
                __DIR__ . '/../' . $this->parameter('elasticsearch.geoshapes_index.indexation.path'),
                $this->parameter('elasticsearch.geoshapes_index.indexation.fileName')
            )
        );

        $this->add(
            InstallGeoShapesCommand::class,
            fn (): InstallGeoShapesCommand => new InstallGeoShapesCommand(
                $this->get(Client::class),
                $this->parameter('elasticsearch.geoshapes_index.prefix') . SchemaVersions::GEOSHAPES,
                $this->parameter('elasticsearch.geoshapes_index.write_alias'),
                $this->parameter('elasticsearch.geoshapes_index.read_alias')
            )
        );

        foreach (AmqpProvider::getConsumers($this) as $consumerKey => $consumerConfig) {
            $name = $this->getAmqpConsumerCommandName($consumerKey);
            $serviceName = 'amqp.' . $consumerKey;
            $queueName = $consumerConfig['queue'] ?? 'unknown';
            $this->add(
                $name,
                fn () => (new ConsumeCommand($name, $this->get($serviceName)))
                    ->setDescription('Process messages from ' . $queueName . ' queue')
            );
        }
    }

    private function getAmqpConsumerCommandName(string $consumerKey): string
    {
        return 'consume-' . $consumerKey;
    }
}
