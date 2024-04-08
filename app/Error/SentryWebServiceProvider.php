<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Error;

use CultuurNet\UDB3\Search\Http\Authentication\Consumer;
use CultuurNet\UDB3\SearchService\BaseServiceProvider;
use Monolog\Logger;
use Sentry\Monolog\Handler as SentryHandler;
use Sentry\State\HubInterface;

final class SentryWebServiceProvider extends BaseServiceProvider
{
    protected $provides = [
        SentryHandlerScopeDecorator::class,
    ];

    public function register(): void
    {
        $this->addShared(
            SentryHandlerScopeDecorator::class,
            fn (): SentryHandlerScopeDecorator => SentryHandlerScopeDecorator::forWeb(
                new SentryHandler($this->get(HubInterface::class), Logger::ERROR),
                $this->get(Consumer::class)
            )
        );
    }
}
