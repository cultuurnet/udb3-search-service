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
        $this->add(Client::class, fn (): Client => $this->buildElasticSearchClient());

        $this->addShared(
            'elasticsearch_indexation_strategy',
            fn (): MutableIndexationStrategy => new MutableIndexationStrategy(
                new SingleFileIndexationStrategy(
                    $this->get(Client::class),
                    $this->get('logger.amqp.udb3')
                )
            )
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
        $version = $this->parameter('elasticsearch.version') ?? 5;
        $host = $version === 8
            ? ($this->parameter('elasticsearch.host8') ?? $this->parameter('elasticsearch.host'))
            : $this->parameter('elasticsearch.host');

        $builder = ClientBuilder::create()->setHosts([$host]);

        if ($version === 8) {
            // The ES7 PHP client requires these headers when connecting to ES8 so that ES8 activates its
            // REST API compatibility layer and accepts v7-shaped requests/responses. This can be removed
            // once the service is fully migrated to the ES8 PHP client (elastic/elasticsearch ^8).
            $builder->setConnectionParams([
                'client' => [
                    'headers' => [
                        'Content-Type' => ['application/vnd.elasticsearch+json;compatible-with=7'],
                        'Accept'       => ['application/vnd.elasticsearch+json;compatible-with=7'],
                    ],
                ],
            ]);
        }

        return $builder->build();
    }
}
