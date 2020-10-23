<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService;

use CultuurNet\UDB3\SearchService\Error\SentryExceptionHandler;
use Sentry\State\HubInterface;

class SentryCliServiceProvider extends BaseServiceProvider
{
    protected $provides = [
        SentryExceptionHandler::class,
    ];

    public function register(): void
    {
        $this->add(
            SentryExceptionHandler::class,
            function () {
                return SentryExceptionHandler::createForCli(
                    $this->get(HubInterface::class)
                );
            }
        );
    }
}
