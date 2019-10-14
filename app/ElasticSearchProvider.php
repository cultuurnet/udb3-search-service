<?php declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService;

use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchDocumentRepository;
use CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy\MutableIndexationStrategy;
use CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy\SingleFileIndexationStrategy;
use CultuurNet\UDB3\Search\ElasticSearch\Offer\GeoShapeQueryOfferRegionService;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Silex\Application;
use ValueObjects\StringLiteral\StringLiteral;

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
        
        $this->add('elasticsearch_transformer_logger',
            function () {
                $logger = new Logger('elasticsearch.transformer');
                
                /** @TODO: fix dir path */
                $logger->pushHandler(
                    new StreamHandler(
                        __DIR__ . '/../log/elasticsearch_transformer.log',
                        Logger::DEBUG
                    )
                );
                
                return $logger;
            }
        );
        
        $this->add('offer_region_service',
            function () {
                return new GeoShapeQueryOfferRegionService(
                    $this->get(Client::class),
                    new StringLiteral($this->parameter('elasticsearch.region.read_index'))
                );
            }
        );
    }
}
