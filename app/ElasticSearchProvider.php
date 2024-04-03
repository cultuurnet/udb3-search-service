<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService;

use CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy\MutableIndexationStrategy;
use CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy\SingleFileIndexationStrategy;
use CultuurNet\UDB3\Search\ElasticSearch\Region\GeoShapeQueryRegionService;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;

final class ElasticSearchProvider extends BaseServiceProvider
{
    protected $provides = [
        Client::class,
        GeoShapeQueryRegionService::class,
        'elasticsearch_indexation_strategy',
    ];

    public function register(): void
    {
        $this->add(
            Client::class,
            function (): Client {
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
            function (): MutableIndexationStrategy {
                return new MutableIndexationStrategy(
                    new SingleFileIndexationStrategy(
                        $this->get(Client::class),
                        $this->get('logger.amqp.udb3')
                    )
                );
            }
        );

        $this->add(
            GeoShapeQueryRegionService::class,
            function (): GeoShapeQueryRegionService {
                return new GeoShapeQueryRegionService(
                    $this->get(Client::class),
                    $this->parameter('elasticsearch.region.read_index')
                );
            }
        );
    }
}
