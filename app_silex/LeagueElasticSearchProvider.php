<?php declare(strict_types=1);


namespace CultuurNet\UDB3\SearchService;


use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;

class LeagueElasticSearchProvider extends BaseServiceProvider
{
    protected $provides = [
        Client::class,
    ];
    /**
     * Use the register method to register items with the container via the
     * protected $this->leagueContainer property or the `getLeagueContainer` method
     * from the ContainerAwareTrait.
     *
     * @return void
     */
    public function register()
    {
        $this->add(Client::class,
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