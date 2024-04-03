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

    public function handle(): ?int
    {
        $throwable = $this->getInspector()->getException();

        // Only log throwables that result in a 5xx response status. 4xx responses are caused by the client so do not
        // need to be fixed on our end.
        $apiProblem = ApiProblemFactory::createFromThrowable($throwable);
        if ($apiProblem->getStatus() >= 500) {
            // Include the original throwable as "exception" so that the Sentry monolog handler can process it correctly.
            $this->logger->error($throwable->getMessage(), ['exception' => $throwable]);
        }

        return null;
    }
}
