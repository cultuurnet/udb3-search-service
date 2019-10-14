<?php declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService;

use GuzzleHttp\Client;

class HttpClientProvider extends BaseServiceProvider
{
    
    /**
     * Use the register method to register items with the container via the
     * protected $this->leagueContainer property or the `getLeagueContainer` method
     * from the ContainerAwareTrait.
     *
     * @return void
     */
    public function register()
    {
        $this->add(
            'http_client',
            function () {
                return new Client();
            }
        );
    }
}
