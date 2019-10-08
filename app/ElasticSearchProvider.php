<?php declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;

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
    }
}
