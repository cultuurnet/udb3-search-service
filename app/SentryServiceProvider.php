<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService;

use Sentry\ClientBuilder;
use Sentry\State\Hub;
use Sentry\State\HubInterface;

class SentryServiceProvider extends BaseServiceProvider
{
    protected $provides = [
        HubInterface::class,
    ];

    public function register()
    {
        $this->add(
            HubInterface::class,
            function () {
                return new Hub(
                    ClientBuilder::create(
                        [
                            'dsn' => $this->parameter('sentry.dsn'),
                            'environment' => $this->parameter('sentry.environment'),
                        ]
                    )->getClient()
                );
            }
        );
    }
}
