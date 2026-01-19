<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService;

use CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy\MutableIndexationStrategy;
use CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy\SingleFileIndexationStrategy;
use CultuurNet\UDB3\Search\ElasticSearch\Region\GeoShapeQueryRegionService;
use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchClientInterface;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;

final class ElasticSearchProvider extends BaseServiceProvider
{
    protected $provides = [
        ElasticSearchClientInterface::class,
        GeoShapeQueryRegionService::class,
        'elasticsearch_indexation_strategy',
    ];

    public function register(): void
    {
        $this->add(
            ElasticSearchClientInterface::class,
            fn (): Client => ClientBuilder::create()
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
                    $this->get(ElasticSearchClientInterface::class),
                    $this->get('logger.amqp.udb3')
                )
            )
        );

        $this->add(
            GeoShapeQueryRegionService::class,
            fn (): GeoShapeQueryRegionService => new GeoShapeQueryRegionService(
                $this->get(ElasticSearchClientInterface::class),
                $this->parameter('elasticsearch.region.read_index')
            )
        );
    }
}
