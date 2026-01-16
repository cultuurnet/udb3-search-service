<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService;

use CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy\MutableIndexationStrategy;
use CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy\SingleFileIndexationStrategy;
use CultuurNet\UDB3\Search\ElasticSearch\Region\GeoShapeQueryRegionService;
use Elastic\Elasticsearch\ClientInterface;
use Elastic\Elasticsearch\ClientBuilder;

final class ElasticSearchProvider extends BaseServiceProvider
{
    protected $provides = [
        ClientInterface::class,
        GeoShapeQueryRegionService::class,
        'elasticsearch_indexation_strategy',
    ];

    public function register(): void
    {
        $this->add(
            ClientInterface::class,
            fn (): ClientInterface => ClientBuilder::create()
                ->setHosts(
                    [
                        $this->parameter('elasticsearch.host'),
                    ]
                )
                ->build()
        );

        $this->addShared(
            'elasticsearch_indexation_strategy',
            fn (): MutableIndexationStrategy => new MutableIndexationStrategy(
                new SingleFileIndexationStrategy(
                    $this->get(ClientInterface::class),
                    $this->get('logger.amqp.udb3')
                )
            )
        );

        $this->add(
            GeoShapeQueryRegionService::class,
            fn (): GeoShapeQueryRegionService => new GeoShapeQueryRegionService(
                $this->get(ClientInterface::class),
                $this->parameter('elasticsearch.region.read_index')
            )
        );
    }
}
