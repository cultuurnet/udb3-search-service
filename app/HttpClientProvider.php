<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService;

use GuzzleHttp\Client;

class HttpClientProvider extends BaseServiceProvider
{
    protected $provides = [
        'http_client',
    ];

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
