<?php declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService;

use CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy\MutableIndexationStrategy;
use CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy\SingleFileIndexationStrategy;
use CultuurNet\UDB3\Search\ElasticSearch\Offer\GeoShapeQueryOfferRegionService;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use ValueObjects\StringLiteral\StringLiteral;

class ElasticSearchProvider extends BaseServiceProvider
{
    protected $provides = [
        Client::class,
        'offer_region_service',
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
                        $this->get('logger.amqp.udb3_consumer')
                    )
                );
            }
        );

        $this->add(
            'offer_region_service',
            function () {
                return new GeoShapeQueryOfferRegionService(
                    $this->get(Client::class),
                    new StringLiteral($this->parameter('elasticsearch.region.read_index'))
                );
            }
        );
    }
}
