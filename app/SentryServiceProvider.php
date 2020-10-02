<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService;

use Sentry\SentrySdk;
use Sentry\State\HubInterface;
use function Sentry\init;

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
                init([
                    'dsn' => $this->parameter('sentry.dsn'),
                    'environment' => $this->parameter('sentry.environment'),
                ]);

                return SentrySdk::getCurrentHub();
            }
        );
    }
}
