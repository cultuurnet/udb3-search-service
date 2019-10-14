<?php declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService;

use CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy\MutableIndexationStrategy;
use CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy\SingleFileIndexationStrategy;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Silex\Application;

class ElasticSearchProvider extends BaseServiceProvider
{
    protected $provides = [
        Client::class,
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
        
        $this->add('elasticsearch_indexation_strategy',
            function () {
                return new MutableIndexationStrategy(
                    new SingleFileIndexationStrategy(
                        $this->get(Client::class),
                        $this->get('logger.amqp.udb3_consumer')
                    )
                );
            }
        );
    }
}
