<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService;

use CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy\MutableIndexationStrategy;
use CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy\SingleFileIndexationStrategy;
use CultuurNet\UDB3\Search\ElasticSearch\Offer\GeoShapeQueryRegionService;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;

final class ElasticSearchProvider extends BaseServiceProvider
{
    protected $provides = [
        Client::class,
        'offer_region_service',
        'elasticsearch_indexation_strategy',
    ];

    public function register()
    {
        $this->add(
            Client::class,
            function () {
                return ClientBuilder::create()
                    ->setHosts(
                        [
                            $this->parameter('elasticsearch.host'),
                        ]
                    )
                    ->build();
            }
        );

        $this->addShared(
            'elasticsearch_indexation_strategy',
            function () {
                return new MutableIndexationStrategy(
                    new SingleFileIndexationStrategy(
                        $this->get(Client::class),
                        $this->get('logger.amqp.udb3')
                    )
                );
            }
        );

        $this->add(
            'offer_region_service',
            function () {
                return new GeoShapeQueryRegionService(
                    $this->get(Client::class),
                    $this->parameter('elasticsearch.region.read_index')
                );
            }
        );
    }
}
