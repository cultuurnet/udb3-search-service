<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Error;

use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKey;
use CultuurNet\UDB3\SearchService\BaseServiceProvider;
use Sentry\State\HubInterface;

final class SentryWebServiceProvider extends BaseServiceProvider
{
    protected $provides = [
        SentryTagsProcessor::class,
        SentryExceptionHandler::class,
    ];

    public function register(): void
    {
        $this->add(
            SentryTagsProcessor::class,
            function () {
                return SentryTagsProcessor::forWeb($this->get(ApiKey::class));
            }
        );

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
