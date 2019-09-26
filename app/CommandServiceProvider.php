<?php declare(strict_types=1);


namespace CultuurNet\UDB3\SearchService;


use CultuurNet\UDB3\SearchService\Console\CheckIndexExistsCommand;
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
use Symfony\Component\Console\Application;

class CommandServiceProvider extends BaseServiceProvider
{
    protected $provides = [
        Application::class
    ];
    
    /**
     * Use the register method to register items with the container via the
     * protected $this->leagueContainer property or the `getLeagueContainer` method
     * from the ContainerAwareTrait.
     *
     * @return void
     */
    public function register()
    {
        $this->add(Application::class,
            function () {
                $application = new Application('udb3-search');
                $application->add($this->get(TermTaxonomyToFacetMappingsCommand::class));
                
                $application->add($this->get(FlandersRegionTaxonomyToFacetMappingsCommand::class));
                
                /** Elasticsearch */
                $application->add($this->get(MigrateElasticSearchCommand::class));
                
                /** Templates */
                $application->add($this->get(CreateLowerCaseExactMatchAnalyzerCommand::class));
                $application->add($this->get(CreateLowerCaseStandardAnalyzerCommand::class));
                $application->add($this->get(CreateAutocompleteAnalyzerCommand::class));
                
                /** Generic index commands. */
                $application->add($this->get(CheckIndexExistsCommand::class));
                $application->add($this->get(CreateIndexCommand::class));
                $application->add($this->get(DeleteIndexCommand::class));
                $application->add($this->get(UpdateIndexAliasCommand::class));
                
                /** UDB3 core. */
                $application->add($this->get(UpdateOrganizerMappingCommand::class));
                $application->add($this->get(UpdateEventMappingCommand::class));
                
                $application->add($this->get(UpdatePlaceMappingCommand::class));
    
    
                $application->add(
                    new UpdateRegionQueryMappingCommand(
                        $this->parameter('elasticsearch.udb3_core_index.latest'),
                        $this->parameter('elasticsearch.region_query.document_type')
                    )
                );
    
                $application->add(
                    new ReindexUDB3CoreCommand(
                        $this->parameter('elasticsearch.udb3_core_index.reindexation.from'),
                        $this->parameter('elasticsearch.udb3_core_index.reindexation.scroll_ttl'),
                        $this->parameter('elasticsearch.udb3_core_index.reindexation.scroll_size'),
                        $this->parameter('elasticsearch.udb3_core_index.reindexation.bulk_threshold')
                    )
                );
    
                $application->add(
                    new ReindexPermanentOffersCommand(
                        $this->parameter('elasticsearch.udb3_core_index.reindexation.from'),
                        $this->parameter('elasticsearch.udb3_core_index.reindexation.scroll_ttl'),
                        $this->parameter('elasticsearch.udb3_core_index.reindexation.scroll_size'),
                        $this->parameter('elasticsearch.udb3_core_index.reindexation.bulk_threshold')
                    )
                );
    
                $application->add(
                    new InstallUDB3CoreCommand(
                        $this->parameter('elasticsearch.udb3_core_index.latest'),
                        $this->parameter('elasticsearch.udb3_core_index.write_alias'),
                        $this->parameter('elasticsearch.udb3_core_index.read_alias')
                    )
                );
    
                /**
                 * Geoshapes
                 */
                $application->add(
                    new UpdateRegionMappingCommand(
                        $this->parameter('elasticsearch.geoshapes_index.latest'),
                        $this->parameter('elasticsearch.region.document_type')
                    )
                );
    
                $application->add(
                    new IndexRegionsCommand(
                        $this->parameter('elasticsearch.geoshapes_index.indexation.to'),
                        __DIR__ . '/../' . $this->parameter('elasticsearch.geoshapes_index.indexation.path'),
                        $this->parameter('elasticsearch.geoshapes_index.indexation.fileName')
                    )
                );
    
                $application->add(
                    new InstallGeoShapesCommand(
                        $this->parameter('elasticsearch.geoshapes_index.latest'),
                        $this->parameter('elasticsearch.geoshapes_index.write_alias'),
                        $this->parameter('elasticsearch.geoshapes_index.read_alias')
                    )
                );
                
                return $application;
            });
        
        $this->add(
            UpdateOrganizerMappingCommand::class,
            function () {
                return new UpdateOrganizerMappingCommand(
                    $this->parameter('elasticsearch.udb3_core_index.latest'),
                    $this->parameter('elasticsearch.organizer.document_type')
                );
            }
        );
        
        $this->add(
            UpdateEventMappingCommand::class,
            function () {
                return new UpdateEventMappingCommand(
                    $this->parameter('elasticsearch.udb3_core_index.latest'),
                    $this->parameter('elasticsearch.event.document_type')
                );
            }
        );
        $this->add(
            UpdatePlaceMappingCommand::class,
            function () {
                return new UpdatePlaceMappingCommand(
                    $this->parameter('elasticsearch.udb3_core_index.latest'),
                    $this->parameter('elasticsearch.place.document_type')
                );
            }
        );
        
    }
}