<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Error;

use CultuurNet\UDB3\SearchService\BaseServiceProvider;
use Sentry\SentrySdk;
use Sentry\State\HubInterface;
use function Sentry\init;

class SentryHubServiceProvider extends BaseServiceProvider
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
