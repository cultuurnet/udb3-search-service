<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Error;

use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKey;
use CultuurNet\UDB3\SearchService\BaseServiceProvider;
use Monolog\Logger;
use Sentry\Monolog\Handler as SentryHandler;
use Sentry\State\HubInterface;

final class SentryWebServiceProvider extends BaseServiceProvider
{
    protected $provides = [
        SentryHandlerScopeDecorator::class,
        SentryTagsProcessor::class,
    ];

    public function register(): void
    {
        $this->addShared(
            SentryHandlerScopeDecorator::class,
            function () {
                return SentryHandlerScopeDecorator::forWeb(
                    new SentryHandler($this->get(HubInterface::class), Logger::ERROR),
                    $this->get(ApiKey::class)
                );
            }
        );

        $this->add(
            SentryTagsProcessor::class,
            function () {
                return SentryTagsProcessor::forWeb($this->get(ApiKey::class));
            }
        );
    }
}
