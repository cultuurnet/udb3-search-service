<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService;

use CultuurNet\UDB3\Search\ElasticSearch\Client\ElasticSearchClient;
use CultuurNet\UDB3\Search\ElasticSearch\Client\ElasticsearchPhpClient;
use CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy\MutableIndexationStrategy;
use CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy\SingleFileIndexationStrategy;
use CultuurNet\UDB3\Search\ElasticSearch\Region\GeoShapeQueryRegionService;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;

final class ElasticSearchProvider extends BaseServiceProvider
{
    protected $provides = [
        Client::class,
        ElasticSearchClient::class,
        GeoShapeQueryRegionService::class,
        'elasticsearch_indexation_strategy',
    ];

    public function register(): void
    {
        $this->add(Client::class, fn (): Client => $this->buildElasticSearchClient());

        $this->add(
            ElasticSearchClient::class,
            fn (): ElasticSearchClient => new ElasticsearchPhpClient($this->get(Client::class))
        );

        $this->addShared(
            'elasticsearch_indexation_strategy',
            function (): MutableIndexationStrategy {
                $strategy = new SingleFileIndexationStrategy(
                    $this->get(ElasticSearchClient::class),
                    $this->get('logger.amqp.udb3')
                );
                return new MutableIndexationStrategy($strategy);
            }
        );

        $this->add(
            GeoShapeQueryRegionService::class,
            fn (): GeoShapeQueryRegionService => new GeoShapeQueryRegionService(
                $this->get(Client::class),
                $this->parameter('elasticsearch.region.read_index')
            )
        );
    }

    private function buildElasticSearchClient(): Client
    {
        return ClientBuilder::create()
            ->setHosts([$this->parameter('elasticsearch.host')])
            ->build();
    }
}
