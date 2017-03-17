<?php

namespace CultuurNet\UDB3\SearchService\Organizer;

use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchDocumentRepository;
use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchPagedResultSetFactory;
use CultuurNet\UDB3\Search\ElasticSearch\Organizer\ElasticSearchOrganizerSearchService;
use CultuurNet\UDB3\Search\ElasticSearch\Organizer\OrganizerJsonDocumentTransformer;
use Silex\Application;
use Silex\ServiceProviderInterface;
use ValueObjects\StringLiteral\StringLiteral;

class OrganizerElasticSearchServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Application $app
     */
    public function register(Application $app)
    {
        $app['organizer_elasticsearch_service'] = $app->share(
            function (Application $app) {
                return new ElasticSearchOrganizerSearchService(
                    $app['elasticsearch_client'],
                    new StringLiteral($app['elasticsearch.organizer.read_index']),
                    new StringLiteral($app['elasticsearch.organizer.document_type']),
                    new ElasticSearchPagedResultSetFactory()
                );
            }
        );

        $app['organizer_elasticsearch_repository'] = $app->share(
            function (Application $app) {
                return new ElasticSearchDocumentRepository(
                    $app['elasticsearch_client'],
                    new StringLiteral($app['elasticsearch.organizer.write_index']),
                    new StringLiteral($app['elasticsearch.organizer.document_type'])
                );
            }
        );

        $app['organizer_elasticsearch_transformer'] = $app->share(
            function () {
                return new OrganizerJsonDocumentTransformer();
            }
        );
    }

    /**
     * @param Application $app
     */
    public function boot(Application $app)
    {
    }
}
