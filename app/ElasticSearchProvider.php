<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService;

use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchClient;
use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchClientDecorator;
use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchClientInterface;
use CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy\MutableIndexationStrategy;
use CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy\SingleFileIndexationStrategy;
use CultuurNet\UDB3\Search\ElasticSearch\Region\GeoShapeQueryRegionService;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Response\Elasticsearch;

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
            function (): ElasticSearchClientInterface {
                $host = $this->parameter('elasticsearch.host');
                // Ensure host has protocol and port
                if (!str_starts_with($host, 'http://') && !str_starts_with($host, 'https://')) {
                    $host = 'http://' . $host . ':9200';
                }

                $client = ClientBuilder::create()
                    ->setHosts([$host])
//                    ->setLogger($this->get('logger.amqp.udb3'))
                    ->build();

                // In the new verison of ES you need to specify Content-Type
                $client->getTransport()->setHeader('Content-Type', 'application/json');
                $client->getTransport()->setHeader(Elasticsearch::HEADER_CHECK, Elasticsearch::PRODUCT_NAME);

                return new ElasticSearchClientDecorator(
                    $client
                );
            }
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
