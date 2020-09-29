<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Error;

use Sentry\State\HubInterface;
use Whoops\Handler\Handler;

class SentryExceptionHandler extends Handler
{
    /** @var HubInterface */
    private $sentryHub;

    public function __construct(HubInterface $sentryHub)
    {
        $this->sentryHub = $sentryHub;
    }

    public function handle(): void
    {
        $exception = $this->getInspector()->getException();
        $this->sentryHub->captureException($exception);
    }
}
