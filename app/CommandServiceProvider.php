<?php declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService;

use Broadway\EventHandling\EventBusInterface;
use CultuurNet\UDB3\Search\ElasticSearch\Operations\SchemaVersions;
use CultuurNet\UDB3\SearchService\Console\CheckIndexExistsCommand;
use CultuurNet\UDB3\SearchService\Console\ConsumeCommand;
use CultuurNet\UDB3\SearchService\Console\CreateAutocompleteAnalyzerCommand;
use CultuurNet\UDB3\SearchService\Console\CreateIndexCommand;
use CultuurNet\UDB3\SearchService\Console\CreateLowerCaseExactMatchAnalyzerCommand;
use CultuurNet\UDB3\SearchService\Console\CreateLowerCaseStandardAnalyzerCommand;
use CultuurNet\UDB3\SearchService\Console\DeleteIndexCommand;
use CultuurNet\UDB3\SearchService\Console\FlandersRegionTaxonomyToFacetMappingsCommand;
use CultuurNet\UDB3\SearchService\Console\IndexRegionsCommand;
use CultuurNet\UDB3\SearchService\Console\InstallGeoShapesCommand;
use CultuurNet\UDB3\SearchService\Console\InstallUDB3CoreCommand;
use CultuurNet\UDB3\SearchService\Console\MigrateElasticSearchCommand;
use CultuurNet\UDB3\SearchService\Console\ReindexPermanentOffersCommand;
use CultuurNet\UDB3\SearchService\Console\ReindexUDB3CoreCommand;
use CultuurNet\UDB3\SearchService\Console\TermTaxonomyToFacetMappingsCommand;
use CultuurNet\UDB3\SearchService\Console\UpdateEventMappingCommand;
use CultuurNet\UDB3\SearchService\Console\UpdateIndexAliasCommand;
use CultuurNet\UDB3\SearchService\Console\UpdateOrganizerMappingCommand;
use CultuurNet\UDB3\SearchService\Console\UpdatePlaceMappingCommand;
use CultuurNet\UDB3\SearchService\Console\UpdateRegionMappingCommand;
use CultuurNet\UDB3\SearchService\Console\UpdateRegionQueryMappingCommand;
use Elasticsearch\Client;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\CommandLoader\FactoryCommandLoader;

class CommandServiceProvider extends BaseServiceProvider
{
    protected $provides = [
        Application::class,
    ];

    public function register()
    {
        $this->add(
            Application::class,
            function () {
                $application = new Application('udb3-search');

                $commandLoader = new FactoryCommandLoader(
                    [
                        'consume-udb3-core' => function () {
                            return (new ConsumeCommand('consume-udb3-core', 'amqp.udb3-core'))
                                ->setDescription('Process messages from UDB3 core');
                        },
                        'facet-mapping:generate-from-taxonomy-terms' => function () {
                            return $this->get(TermTaxonomyToFacetMappingsCommand::class);
                        },
                        'facet-mapping:generate-regions-from-flandersregion-terms' => function () {
                            return $this->get(FlandersRegionTaxonomyToFacetMappingsCommand::class);
                        },
                        'elasticsearch:migrate' => function () {
                            return $this->get(MigrateElasticSearchCommand::class);
                        },
                        'lowercase-exact-match-analyzer:create' => function () {
                            return $this->get(CreateLowerCaseExactMatchAnalyzerCommand::class);
                        },
                        'lowercase-standard-analyzer:create' => function () {
                            return $this->get(CreateLowerCaseStandardAnalyzerCommand::class);
                        },
                        'autocomplete-analyzer:create' => function () {
                            return $this->get(CreateAutocompleteAnalyzerCommand::class);
                        },
                        'index:exists' => function () {
                            return $this->get(CheckIndexExistsCommand::class);
                        },
                        'index:create' => function () {
                            return $this->get(CreateIndexCommand::class);
                        },
                        'index:delete' => function () {
                            return $this->get(DeleteIndexCommand::class);
                        },
                        'index:update-alias' => function () {
                            return $this->get(UpdateIndexAliasCommand::class);
                        },
                        'udb3-core:organizer-mapping' => function () {
                            return $this->get(UpdateOrganizerMappingCommand::class);
                        },
                        'udb3-core:event-mapping' => function () {
                            return $this->get(UpdateEventMappingCommand::class);
                        },

                        'udb3-core:place-mapping' => function () {
                            return $this->get(UpdatePlaceMappingCommand::class);
                        },
                        'udb3-core:region-query-mapping' => function () {
                            return new UpdateRegionQueryMappingCommand(
                                $this->get(Client::class),
                                $this->parameter('elasticsearch.udb3_core_index.latest'),
                                $this->parameter('elasticsearch.region_query.document_type')
                            );
                        },
                        'udb3-core:reindex' => function () {
                            return new ReindexUDB3CoreCommand(
                                $this->get(Client::class),
                                $this->parameter('elasticsearch.udb3_core_index.reindexation.from'),
                                $this->get(EventBusInterface::class),
                                $this->get('elasticsearch_indexation_strategy'),
                                $this->parameter('elasticsearch.udb3_core_index.reindexation.scroll_ttl'),
                                $this->parameter('elasticsearch.udb3_core_index.reindexation.scroll_size'),
                                $this->parameter('elasticsearch.udb3_core_index.reindexation.bulk_threshold')
                            );
                        },
                        'udb3-core:reindex-permanent' => function () {
                            return new ReindexPermanentOffersCommand(
                                $this->get(Client::class),
                                $this->parameter('elasticsearch.udb3_core_index.reindexation.from'),
                                $this->get(EventBusInterface::class),
                                $this->get('elasticsearch_indexation_strategy'),
                                $this->parameter('elasticsearch.udb3_core_index.reindexation.scroll_ttl'),
                                $this->parameter('elasticsearch.udb3_core_index.reindexation.scroll_size'),
                                $this->parameter('elasticsearch.udb3_core_index.reindexation.bulk_threshold')
                            );
                        },
                        'udb3-core:install' => function () {
                            return new InstallUDB3CoreCommand(
                                $this->get(Client::class),
                                $this->parameter('elasticsearch.udb3_core_index.prefix') . SchemaVersions::UDB3_CORE,
                                $this->parameter('elasticsearch.udb3_core_index.write_alias'),
                                $this->parameter('elasticsearch.udb3_core_index.read_alias')
                            );
                        },
                        'geoshapes:region-mapping' => function () {
                            return new UpdateRegionMappingCommand(
                                $this->get(Client::class),
                                $this->parameter('elasticsearch.geoshapes_index.prefix') . SchemaVersions::GEOSHAPES,
                                $this->parameter('elasticsearch.region.document_type')
                            );
                        },
                        'geoshapes:index-regions' => function () {
                            return new IndexRegionsCommand(
                                $this->get(Client::class),
                                $this->parameter('elasticsearch.geoshapes_index.indexation.to'),
                                __DIR__ . '/../' . $this->parameter('elasticsearch.geoshapes_index.indexation.path'),
                                $this->parameter('elasticsearch.geoshapes_index.indexation.fileName')
                            );
                        },
                        'geoshapes:install' => function () {
                            return new InstallGeoShapesCommand(
                                $this->get(Client::class),
                                $this->parameter('elasticsearch.geoshapes_index.prefix') . SchemaVersions::GEOSHAPES,
                                $this->parameter('elasticsearch.geoshapes_index.write_alias'),
                                $this->parameter('elasticsearch.geoshapes_index.read_alias')
                            );
                        },
                    ]
                );
                $application->setCommandLoader($commandLoader);
                return $application;
            }
        );

        $this->add(
            UpdateOrganizerMappingCommand::class,
            function () {
                return new UpdateOrganizerMappingCommand(
                    $this->get(Client::class),
                    $this->parameter('elasticsearch.udb3_core_index.prefix') . SchemaVersions::UDB3_CORE,
                    $this->parameter('elasticsearch.organizer.document_type')
                );
            }
        );

        $this->add(
            UpdateEventMappingCommand::class,
            function () {
                return new UpdateEventMappingCommand(
                    $this->get(Client::class),
                    $this->parameter('elasticsearch.udb3_core_index.prefix') . SchemaVersions::UDB3_CORE,
                    $this->parameter('elasticsearch.event.document_type')
                );
            }
        );

        $this->add(
            UpdatePlaceMappingCommand::class,
            function () {
                return new UpdatePlaceMappingCommand(
                    $this->get(Client::class),
                    $this->parameter('elasticsearch.udb3_core_index.prefix') . SchemaVersions::UDB3_CORE,
                    $this->parameter('elasticsearch.place.document_type')
                );
            }
        );
    }
}
