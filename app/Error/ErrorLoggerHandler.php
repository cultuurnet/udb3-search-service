<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Error;

use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKeyAuthenticationException;
use CultuurNet\UDB3\ApiGuard\Request\RequestAuthenticationException;
use CultuurNet\UDB3\Search\MissingParameter;
use CultuurNet\UDB3\Search\UnsupportedParameter;
use CultuurNet\UDB3\Search\UnsupportedParameterValue;
use Elasticsearch\Common\Exceptions\BadRequest400Exception;
use League\Route\Http\Exception\MethodNotAllowedException;
use League\Route\Http\Exception\NotFoundException;
use Psr\Log\LoggerInterface;
use Whoops\Handler\Handler;

final class ErrorLoggerHandler extends Handler
{
    private const BAD_REQUEST_EXCEPTIONS = [
        UnsupportedParameter::class,
        UnsupportedParameterValue::class,
        MissingParameter::class,
        ApiKeyAuthenticationException::class,
        RequestAuthenticationException::class,
        NotFoundException::class,
        MethodNotAllowedException::class,
    ];

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

        // Don't log exceptions that are caused by user errors
        if (in_array(get_class($throwable), self::BAD_REQUEST_EXCEPTIONS)) {
            return null;
        }

        // Don't log Elasticsearch exceptions caused by un-parsable query in q parameter, but do log others
        if ($throwable instanceof BadRequest400Exception
            && strpos($throwable->getMessage(), 'Failed to parse query') !== false) {
            return null;
        }

        // Include the original throwable as "exception" so that the Sentry monolog handler can process it correctly.
        $this->logger->error($throwable->getMessage(), ['exception' => $throwable]);

        return null;
    }
}
