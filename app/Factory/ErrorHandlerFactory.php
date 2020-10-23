<?php declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Factory;

use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKey;
use CultuurNet\UDB3\SearchService\Error\ApiExceptionHandler;
use CultuurNet\UDB3\SearchService\Error\SentryExceptionHandler;
use Sentry\State\HubInterface;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;
use Zend\HttpHandlerRunner\Emitter\SapiStreamEmitter;

class ErrorHandlerFactory
{
    public static function forWeb(SentryExceptionHandler $sentryExceptionHandler, bool $isDebugEnvironment): Run
    {
        $whoops = new Run();
        self::prependWebHandler($whoops, $isDebugEnvironment);
        $whoops->prependHandler($sentryExceptionHandler);
        return $whoops;
    }

    public static function forCli(SentryExceptionHandler $sentryExceptionHandler): Run
    {
        $whoops = new Run();
        $whoops->prependHandler(new PlainTextHandler());
        $whoops->prependHandler($sentryExceptionHandler);
        return $whoops;
    }

    private static function prependWebHandler(Run $whoops, bool $isDebugEnvironment): void
    {
        if ($isDebugEnvironment === true) {
            $whoops->prependHandler(new PrettyPageHandler());
            return;
        }

        $whoops->prependHandler(new ApiExceptionHandler(new SapiStreamEmitter()));
    }
}
