<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Error;

use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKey;
use CultuurNet\UDB3\SearchService\Error\SentryExceptionHandler;
use CultuurNet\UDB3\SearchService\BaseServiceProvider;
use Sentry\State\HubInterface;

class SentryWebServiceProvider extends BaseServiceProvider
{
    protected $provides = [
        SentryExceptionHandler::class,
    ];

    public function register(): void
    {
        $this->add(
            SentryExceptionHandler::class,
            function () {
                return SentryExceptionHandler::createForWeb(
                    $this->get(HubInterface::class),
                    $this->get(ApiKey::class)
                );
            }
        );
    }
}
