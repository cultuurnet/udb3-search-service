<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Error;

use Psr\Log\LoggerInterface;
use Whoops\Handler\Handler;

final class ErrorLoggerHandler extends Handler
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function handle(): void
    {
        $throwable = $this->getInspector()->getException();

        // Include the original throwable as "exception" so that the Sentry monolog handler can process it correctly.
        $this->logger->error($throwable->getMessage(), ['exception' => $throwable]);
    }
}
