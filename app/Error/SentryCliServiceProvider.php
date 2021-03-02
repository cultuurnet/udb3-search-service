<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Error;

use CultuurNet\UDB3\SearchService\BaseServiceProvider;
use Sentry\State\HubInterface;

final class SentryCliServiceProvider extends BaseServiceProvider
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
